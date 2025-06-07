<?php
/**
 * Tools API
 * Handles tool discovery and execution via JSON API
 */

class ToolsAPI {
    
    public function index() {
        requireAuth();
        
        try {
            $availableTools = [
                [
                    'name' => 'Math',
                    'class' => 'Math',
                    'description' => 'Perform mathematical calculations and operations',
                    'category' => 'computation'
                ],
                [
                    'name' => 'Search',
                    'class' => 'Search',
                    'description' => 'Search the web for current information and data',
                    'category' => 'information'
                ],
                [
                    'name' => 'Weather',
                    'class' => 'Weather',
                    'description' => 'Get current weather information for any location',
                    'category' => 'information'
                ],
                [
                    'name' => 'ReadPDF',
                    'class' => 'ReadPDF',
                    'description' => 'Extract and analyze text content from PDF documents',
                    'category' => 'document'
                ]
            ];
            
            jsonResponse($availableTools);
        } catch (Exception $e) {
            logger("Error fetching tools: " . $e->getMessage());
            jsonError('Failed to fetch available tools', 500);
        }
    }
    
    public function show($toolName) {
        requireAuth();
        
        try {
            $toolFile = __DIR__ . "/../Tools/{$toolName}.php";
            
            if (!file_exists($toolFile)) {
                jsonError('Tool not found', 404);
            }
            
            require_once $toolFile;
            
            if (!class_exists($toolName)) {
                jsonError('Tool class not found', 404);
            }
            
            $tool = new $toolName();
            
            $toolInfo = [
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'parameters' => $tool->getParametersSchema(),
                'openai_definition' => $tool->getOpenAIDefinition()
            ];
            
            jsonResponse($toolInfo);
        } catch (Exception $e) {
            logger("Error fetching tool info: " . $e->getMessage());
            jsonError('Failed to fetch tool information', 500);
        }
    }
    
    public function execute($toolName) {
        requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (!isset($input['parameters'])) {
            jsonError('Parameters are required', 400);
        }
        
        try {
            $toolFile = __DIR__ . "/../Tools/{$toolName}.php";
            
            if (!file_exists($toolFile)) {
                jsonError('Tool not found', 404);
            }
            
            require_once $toolFile;
            
            if (!class_exists($toolName)) {
                jsonError('Tool class not found', 404);
            }
            
            $tool = new $toolName();
            $result = $tool->safeExecute($input['parameters']);
            
            jsonResponse([
                'success' => true,
                'tool' => $toolName,
                'result' => $result
            ]);
        } catch (Exception $e) {
            logger("Error executing tool: " . $e->getMessage());
            jsonError('Failed to execute tool: ' . $e->getMessage(), 500);
        }
    }
    
    public function validate($toolName) {
        requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        try {
            $toolFile = __DIR__ . "/../Tools/{$toolName}.php";
            
            if (!file_exists($toolFile)) {
                jsonError('Tool not found', 404);
            }
            
            require_once $toolFile;
            
            if (!class_exists($toolName)) {
                jsonError('Tool class not found', 404);
            }
            
            $tool = new $toolName();
            
            // Validate parameters without executing
            $isValid = true;
            $errors = [];
            
            try {
                $tool->validateParameters($input['parameters'] ?? []);
            } catch (Exception $e) {
                $isValid = false;
                $errors[] = $e->getMessage();
            }
            
            jsonResponse([
                'valid' => $isValid,
                'errors' => $errors,
                'tool' => $toolName
            ]);
        } catch (Exception $e) {
            logger("Error validating tool parameters: " . $e->getMessage());
            jsonError('Failed to validate tool parameters', 500);
        }
    }
}