<?php
/**
 * System API
 * Handles system operations, health checks, and AI processing
 */

class SystemAPI {
    private $openaiConfig;
    
    public function __construct() {
        $this->openaiConfig = config('openai');
        
        if (empty($this->openaiConfig['api_key'])) {
            throw new Exception('OpenAI API key not configured');
        }
    }
    
    public function health() {
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'checks' => [
                    'database' => $this->checkDatabase(),
                    'openai' => $this->checkOpenAI(),
                    'tools' => $this->checkTools(),
                    'storage' => $this->checkStorage()
                ]
            ];
            
            // Determine overall status
            $allHealthy = true;
            foreach ($health['checks'] as $check) {
                if (!$check['healthy']) {
                    $allHealthy = false;
                    break;
                }
            }
            
            $health['status'] = $allHealthy ? 'healthy' : 'unhealthy';
            
            jsonResponse($health);
        } catch (Exception $e) {
            logger("Health check error: " . $e->getMessage());
            jsonError('Health check failed', 500);
        }
    }
    
    public function stats() {
        requireAuth();
        
        try {
            require_once __DIR__ . '/../Web/Models/Run.php';
            
            $stats = [
                'user' => [
                    'id' => currentUserId(),
                    'threads' => $this->getUserThreadCount(),
                    'agents' => $this->getUserAgentCount(),
                    'messages' => $this->getUserMessageCount()
                ],
                'runs' => Run::getStats(currentUserId()),
                'system' => [
                    'version' => appVersion(),
                    'uptime' => $this->getSystemUptime(),
                    'memory_usage' => memory_get_usage(true),
                    'peak_memory' => memory_get_peak_usage(true)
                ]
            ];
            
            jsonResponse($stats);
        } catch (Exception $e) {
            logger("Error fetching stats: " . $e->getMessage());
            jsonError('Failed to fetch statistics', 500);
        }
    }
    
    public function processMessage($threadId, $message) {
        // Get conversation history
        require_once __DIR__ . '/../Web/Models/Thread.php';
        $threadModel = new Thread();
        $messages = $threadModel->getMessages($threadId);
        
        // Prepare messages for OpenAI (exclude system messages from history)
        $conversationHistory = [];
        foreach ($messages as $msg) {
            if ($msg['role'] !== 'system') {
                $conversationHistory[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }
        }
        
        // Call OpenAI API
        return $this->sendMessageToOpenAI($message, $conversationHistory);
    }
    
    public function executeAgent($agent, $message, $threadId) {
        // Get conversation history
        require_once __DIR__ . '/../Web/Models/Thread.php';
        $threadModel = new Thread();
        $messages = $threadModel->getMessages($threadId);
        
        // Prepare tools for OpenAI
        $tools = $this->prepareTools($agent->getTools());
        logger("SystemAPI: Prepared " . count($tools) . " tools for agent execution");
        
        // Prepare messages for OpenAI
        $conversationMessages = [
            [
                'role' => 'system',
                'content' => $agent->getInstructions()
            ]
        ];
        
        // Add recent conversation history (last 10 messages)
        $recentMessages = array_slice($messages, -10);
        foreach ($recentMessages as $msg) {
            if ($msg['role'] !== 'system') {
                $conversationMessages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }
        }
        
        // Add current user message
        $conversationMessages[] = [
            'role' => 'user',
            'content' => $message
        ];
        
        // Prepare OpenAI request
        $payload = [
            'model' => $agent->getModel() ?: $this->openaiConfig['model'],
            'messages' => $conversationMessages,
            'max_tokens' => $this->openaiConfig['max_tokens'],
            'temperature' => $this->openaiConfig['temperature']
        ];
        
        // Add tools if available
        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }
        
        // Make initial API call
        $response = $this->callOpenAIAPI($payload);
        
        // Check if agent wants to use tools
        $assistantMessage = $response['choices'][0]['message'];
        
        if (isset($assistantMessage['tool_calls'])) {
            logger("SystemAPI: Agent requested " . count($assistantMessage['tool_calls']) . " tool calls");
            
            // Execute tool calls
            $toolResults = $this->executeToolCalls($assistantMessage['tool_calls']);
            
            // Add assistant message with tool calls to conversation
            $conversationMessages[] = $assistantMessage;
            
            // Add tool results to conversation
            foreach ($toolResults as $toolResult) {
                $conversationMessages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolResult['tool_call_id'],
                    'content' => json_encode($toolResult['result'])
                ];
            }
            
            // Make another API call with tool results
            $payload['messages'] = $conversationMessages;
            $finalResponse = $this->callOpenAIAPI($payload);
            
            return $finalResponse['choices'][0]['message']['content'];
        } else {
            return $assistantMessage['content'];
        }
    }
    
    private function sendMessageToOpenAI($userMessage, $conversationHistory = []) {
        // Prepare messages array for OpenAI
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a helpful AI assistant. Provide clear, helpful, and concise responses.'
            ]
        ];
        
        // Add conversation history (keeping it reasonable length)
        $recentHistory = array_slice($conversationHistory, -10);
        $messages = array_merge($messages, $recentHistory);
        
        // Prepare the API request
        $payload = [
            'model' => $this->openaiConfig['model'],
            'messages' => $messages,
            'max_tokens' => $this->openaiConfig['max_tokens'],
            'temperature' => $this->openaiConfig['temperature'],
            'stream' => false
        ];
        
        // Make the API call
        $response = $this->callOpenAIAPI($payload);
        
        // Extract and return the response
        if (isset($response['choices'][0]['message']['content'])) {
            return trim($response['choices'][0]['message']['content']);
        } else {
            throw new Exception('Invalid response from OpenAI API');
        }
    }
    
    private function prepareTools($toolClassNames) {
        $tools = [];
        
        foreach ($toolClassNames as $toolClassName) {
            try {
                $toolFile = __DIR__ . "/../Tools/{$toolClassName}.php";
                
                if (file_exists($toolFile)) {
                    require_once $toolFile;
                    
                    if (class_exists($toolClassName)) {
                        $tool = new $toolClassName();
                        $tools[] = $tool->getOpenAIDefinition();
                    }
                }
            } catch (Exception $e) {
                logger("SystemAPI: Error loading tool {$toolClassName}: " . $e->getMessage());
            }
        }
        
        return $tools;
    }
    
    private function executeToolCalls($toolCalls) {
        $results = [];
        
        foreach ($toolCalls as $toolCall) {
            try {
                $toolName = $toolCall['function']['name'];
                $parameters = json_decode($toolCall['function']['arguments'], true);
                
                $result = $this->executeTool($toolName, $parameters);
                
                $results[] = [
                    'tool_call_id' => $toolCall['id'],
                    'tool_name' => $toolName,
                    'result' => $result
                ];
                
            } catch (Exception $e) {
                logger("SystemAPI: Tool execution failed for {$toolName}: " . $e->getMessage());
                
                $results[] = [
                    'tool_call_id' => $toolCall['id'],
                    'tool_name' => $toolName,
                    'result' => [
                        'success' => false,
                        'error' => $e->getMessage()
                    ]
                ];
            }
        }
        
        return $results;
    }
    
    private function executeTool($toolName, $parameters) {
        // Map tool names to class names
        $toolMap = [
            'math' => 'Math',
            'calculator' => 'Math',
            'search' => 'Search',
            'web_search' => 'Search',
            'weather' => 'Weather',
            'read_pdf' => 'ReadPDF'
        ];
        
        $toolClassName = $toolMap[$toolName] ?? $toolName;
        $toolFile = __DIR__ . "/../Tools/{$toolClassName}.php";
        
        if (!file_exists($toolFile)) {
            throw new Exception("Tool file not found: {$toolFile}");
        }
        
        require_once $toolFile;
        
        if (!class_exists($toolClassName)) {
            throw new Exception("Tool class not found: {$toolClassName}");
        }
        
        $tool = new $toolClassName();
        return $tool->safeExecute($parameters);
    }
    
    private function callOpenAIAPI($payload) {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openaiConfig['api_key']
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            throw new Exception('cURL error: ' . $curlError);
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON parse error: ' . json_last_error_msg());
        }
        
        if ($httpCode !== 200) {
            $errorMessage = isset($decoded['error']['message']) 
                ? $decoded['error']['message'] 
                : "HTTP error: $httpCode";
            throw new Exception("OpenAI API error: " . $errorMessage);
        }
        
        return $decoded;
    }
    
    // Health check methods
    private function checkDatabase() {
        try {
            db()->query('SELECT 1');
            return ['healthy' => true, 'message' => 'Database connection OK'];
        } catch (Exception $e) {
            return ['healthy' => false, 'message' => 'Database connection failed'];
        }
    }
    
    private function checkOpenAI() {
        try {
            // Simple API test
            $response = $this->callOpenAIAPI([
                'model' => $this->openaiConfig['model'],
                'messages' => [['role' => 'user', 'content' => 'test']],
                'max_tokens' => 1
            ]);
            return ['healthy' => true, 'message' => 'OpenAI API accessible'];
        } catch (Exception $e) {
            return ['healthy' => false, 'message' => 'OpenAI API error: ' . $e->getMessage()];
        }
    }
    
    private function checkTools() {
        $toolsPath = __DIR__ . '/../Tools/';
        $toolFiles = glob($toolsPath . '*.php');
        return [
            'healthy' => count($toolFiles) > 0,
            'message' => count($toolFiles) . ' tools available'
        ];
    }
    
    private function checkStorage() {
        $logsPath = __DIR__ . '/../../logs/';
        $writable = is_writable($logsPath) || is_writable(dirname($logsPath));
        return [
            'healthy' => $writable,
            'message' => $writable ? 'Storage writable' : 'Storage not writable'
        ];
    }
    
    // Stats helper methods
    private function getUserThreadCount() {
        $result = dbQueryOne("SELECT COUNT(*) as count FROM threads WHERE user_id = ?", [currentUserId()]);
        return $result['count'];
    }
    
    private function getUserAgentCount() {
        $result = dbQueryOne("SELECT COUNT(*) as count FROM agents WHERE user_id = ? AND is_active = 1", [currentUserId()]);
        return $result['count'];
    }
    
    private function getUserMessageCount() {
        $result = dbQueryOne("
            SELECT COUNT(m.id) as count 
            FROM messages m 
            JOIN threads t ON m.thread_id = t.id 
            WHERE t.user_id = ?
        ", [currentUserId()]);
        return $result['count'];
    }
    
    private function getSystemUptime() {
        // Simple uptime calculation (since this request started)
        return time() - $_SERVER['REQUEST_TIME'];
    }
}