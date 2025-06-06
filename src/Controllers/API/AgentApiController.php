<?php
// src/Controllers/Api/AgentApiController.php

require_once __DIR__ . '/../../Models/Agent.php';

class AgentApiController {
    
    public function index() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        try {
            // Get user's agents
            $agents = Agent::getUserAgents($_SESSION['user_id']);
            
            // Convert to array format
            $agentData = [];
            foreach ($agents as $agent) {
                $agentData[] = $agent->toArray();
            }
            
            header('Content-Type: application/json');
            echo json_encode($agentData);
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to fetch agents']);
            error_log("Error fetching agents: " . $e->getMessage());
        }
    }
    
    public function store() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (!$input || !isset($input['name']) || !isset($input['instructions'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Name and instructions are required']);
            exit;
        }
        
        try {
            // Create new agent
            $agent = new Agent(
                $input['name'],
                $input['instructions'],
                $input['model'] ?? 'gpt-4o-mini'
            );
            
            // Add tools if provided
            if (isset($input['tools']) && is_array($input['tools'])) {
                foreach ($input['tools'] as $tool) {
                    $agent->addTool($tool);
                }
            }
            
            // Save agent
            $agent->save();
            
            // Return created agent
            http_response_code(201);
            header('Content-Type: application/json');
            echo json_encode($agent->toArray());
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to create agent: ' . $e->getMessage()]);
            error_log("Error creating agent: " . $e->getMessage());
        }
    }
    
    public function show($agentId) {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        try {
            $agent = Agent::findById($agentId);
            
            if (!$agent) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Agent not found']);
                exit;
            }
            
            // Check if agent belongs to user
            if ($agent->getUserId() != $_SESSION['user_id']) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            header('Content-Type: application/json');
            echo json_encode($agent->toArray());
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to fetch agent']);
            error_log("Error fetching agent: " . $e->getMessage());
        }
    }
    
    public function run($agentId) {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (!$input || !isset($input['message']) || !isset($input['threadId'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Message and threadId are required']);
            exit;
        }
        
        try {
            $agent = Agent::findById($agentId);
            
            if (!$agent) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Agent not found']);
                exit;
            }
            
            // Check if agent belongs to user or is public
            if ($agent->getUserId() != $_SESSION['user_id']) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            // Verify thread belongs to user
            require_once __DIR__ . '/../../Models/Thread.php';
            $threadModel = new Thread();
            if (!$threadModel->belongsToUser($input['threadId'], $_SESSION['user_id'])) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Thread access denied']);
                exit;
            }
            
            // Save user message first
            $threadModel->addMessage($input['threadId'], 'user', $input['message']);
            
            // Execute agent
            $response = $agent->execute($input['message'], $input['threadId']);
            
            // Save agent response
            $threadModel->addMessage($input['threadId'], 'assistant', $response);
            
            // Return response
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'response' => $response,
                'agentId' => $agentId,
                'threadId' => $input['threadId']
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to execute agent: ' . $e->getMessage()]);
            error_log("Error executing agent: " . $e->getMessage());
        }
    }
    
    public function update($agentId) {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        try {
            $agent = Agent::findById($agentId);
            
            if (!$agent) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Agent not found']);
                exit;
            }
            
            // Check if agent belongs to user
            if ($agent->getUserId() != $_SESSION['user_id']) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            // Create a new agent with updated properties (this is cleaner than trying to modify private properties)
            $updatedAgent = new Agent(
                $input['name'] ?? $agent->getName(),
                $input['instructions'] ?? $agent->getInstructions(),
                $input['model'] ?? $agent->getModel()
            );
            
            // Set the ID to update existing record
            $updatedAgent->setId($agent->getId());
            
            // Add tools if provided, otherwise keep existing tools
            if (isset($input['tools']) && is_array($input['tools'])) {
                foreach ($input['tools'] as $tool) {
                    $updatedAgent->addTool($tool);
                }
            } else {
                // Keep existing tools
                foreach ($agent->getTools() as $tool) {
                    $updatedAgent->addTool($tool);
                }
            }
            
            // Save changes
            $updatedAgent->save();
            
            header('Content-Type: application/json');
            echo json_encode($updatedAgent->toArray());
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to update agent']);
            error_log("Error updating agent: " . $e->getMessage());
        }
    }
    
    public function destroy($agentId) {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        try {
            $agent = Agent::findById($agentId);
            
            if (!$agent) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Agent not found']);
                exit;
            }
            
            // Check if agent belongs to user
            if ($agent->getUserId() != $_SESSION['user_id']) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            // Soft delete (mark as inactive)
            $agent->delete();
            
            http_response_code(204);
            header('Content-Type: application/json');
            echo json_encode(['message' => 'Agent deleted successfully']);
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to delete agent']);
            error_log("Error deleting agent: " . $e->getMessage());
        }
    }
    
    public function getAvailableTools() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        try {
            // Return list of available tools
            $availableTools = [
                [
                    'name' => 'Calculator',
                    'class' => 'Calculator',
                    'description' => 'Perform mathematical calculations safely'
                ],
                [
                    'name' => 'Web Search',
                    'class' => 'WebSearch',
                    'description' => 'Search the web for current information'
                ],
                [
                    'name' => 'Weather',
                    'class' => 'Weather',
                    'description' => 'Get current weather information for any location'
                ]
            ];
            
            header('Content-Type: application/json');
            echo json_encode($availableTools);
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to fetch available tools']);
            error_log("Error fetching tools: " . $e->getMessage());
        }
    }
}