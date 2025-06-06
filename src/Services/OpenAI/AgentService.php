<?php
// src/Services/OpenAI/AgentService.php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../Models/Thread.php';

class AgentService {
    private $apiKey;
    private $model;
    private $maxTokens;
    private $temperature;
    
    public function __construct() {
        // Load configuration
        $config = require __DIR__ . '/../../../config/config.php';
        
        $this->apiKey = $config['api_key'];
        $this->model = $config['model'];
        $this->maxTokens = $config['max_tokens'];
        $this->temperature = $config['temperature'];
        
        if (empty($this->apiKey)) {
            throw new Exception('OpenAI API key not configured');
        }
    }
    
    public function executeAgent($agent, $message, $threadId) {
        // Get conversation history
        $threadModel = new Thread();
        $messages = $threadModel->getMessages($threadId);
        
        // Prepare tools for OpenAI
        $tools = $this->prepareTools($agent->getTools());
        
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
            'model' => $agent->getModel() ?: $this->model,
            'messages' => $conversationMessages,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature
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
            // No tools needed, return response directly
            return $assistantMessage['content'];
        }
    }
    
    private function prepareTools($toolClassNames) {
        $tools = [];
        
        foreach ($toolClassNames as $toolClassName) {
            try {
                error_log("AgentService: Loading tool: $toolClassName");
                
                // Load the tool class
                $toolFile = __DIR__ . "/../../Tools/{$toolClassName}.php";
                error_log("AgentService: Tool file path: $toolFile");
                
                if (file_exists($toolFile)) {
                    require_once $toolFile;
                    error_log("AgentService: Tool file loaded successfully");
                    
                    if (class_exists($toolClassName)) {
                        $tool = new $toolClassName();
                        $tools[] = $tool->getOpenAIDefinition();
                        error_log("AgentService: Tool class instantiated: $toolClassName");
                    } else {
                        error_log("AgentService: Tool class not found: $toolClassName");
                    }
                } else {
                    error_log("AgentService: Tool file not found: $toolFile");
                }
            } catch (Exception $e) {
                error_log("AgentService: Error loading tool {$toolClassName}: " . $e->getMessage());
                error_log("AgentService: Error trace: " . $e->getTraceAsString());
            }
        }
        
        error_log("AgentService: Prepared " . count($tools) . " tools");
        return $tools;
    }
    
    private function executeToolCalls($toolCalls) {
        $results = [];
        
        foreach ($toolCalls as $toolCall) {
            try {
                $toolName = $toolCall['function']['name'];
                $parameters = json_decode($toolCall['function']['arguments'], true);
                
                // Load and execute the tool
                $result = $this->executeTool($toolName, $parameters);
                
                $results[] = [
                    'tool_call_id' => $toolCall['id'],
                    'tool_name' => $toolName,
                    'result' => $result
                ];
                
            } catch (Exception $e) {
                $results[] = [
                    'tool_call_id' => $toolCall['id'],
                    'tool_name' => $toolCall['function']['name'] ?? 'unknown',
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
            'calculator' => 'Calculator',
            'web_search' => 'WebSearch', 
            'weather' => 'Weather'
        ];
        
        if (!isset($toolMap[$toolName])) {
            throw new Exception("Unknown tool: {$toolName}");
        }
        
        $toolClassName = $toolMap[$toolName];
        $toolFile = __DIR__ . "/../../Tools/{$toolClassName}.php";
        
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
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Longer timeout for tool calls
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Handle curl errors
        if ($response === false) {
            throw new Exception('cURL error: ' . $curlError);
        }
        
        // Parse JSON response
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON parse error: ' . json_last_error_msg());
        }
        
        // Handle HTTP errors
        if ($httpCode !== 200) {
            $errorMessage = isset($decoded['error']['message']) 
                ? $decoded['error']['message'] 
                : "HTTP error: $httpCode";
            throw new Exception("OpenAI API error: " . $errorMessage);
        }
        
        return $decoded;
    }
}