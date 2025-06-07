<?php
/**
 * Agents API
 * Handles agent management and execution via JSON API
 */

require_once __DIR__ . '/../Web/Models/Agent.php';

class AgentsAPI {
    
    public function index() {
        requireAuth();
        
        try {
            $agents = Agent::getUserAgents(currentUserId());
            
            // Convert to array format
            $agentData = [];
            foreach ($agents as $agent) {
                $agentData[] = $agent->toArray();
            }
            
            jsonResponse($agentData);
        } catch (Exception $e) {
            logger("Error fetching agents: " . $e->getMessage());
            jsonError('Failed to fetch agents', 500);
        }
    }
    
    public function store() {
        requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        $errors = validateInput($input ?? [], [
            'name' => 'required',
            'instructions' => 'required'
        ]);
        
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
            
            jsonResponse($agent->toArray(), 201);
        } catch (Exception $e) {
            logger("Error creating agent: " . $e->getMessage());
            jsonError('Failed to create agent: ' . $e->getMessage(), 500);
        }
    }
    
    public function show($agentId) {
        requireAuth();
        
        try {
            $agent = Agent::findById($agentId);
            
            if (!$agent) {
                jsonError('Agent not found', 404);
            }
            
            // Check if agent belongs to user
            if ($agent->getUserId() != currentUserId()) {
                jsonError('Access denied', 403);
            }
            
            jsonResponse($agent->toArray());
        } catch (Exception $e) {
            logger("Error fetching agent: " . $e->getMessage());
            jsonError('Failed to fetch agent', 500);
        }
    }
    
    public function run($agentId) {
        requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        $errors = validateInput($input ?? [], [
            'message' => 'required',
            'threadId' => 'required'
        ]);
        
        try {
            $agent = Agent::findById($agentId);
            
            if (!$agent) {
                jsonError('Agent not found', 404);
            }
            
            // Check if agent belongs to user
            if ($agent->getUserId() != currentUserId()) {
                jsonError('Access denied', 403);
            }
            
            // Verify thread belongs to user
            require_once __DIR__ . '/../Web/Models/Thread.php';
            $threadModel = new Thread();
            if (!$threadModel->belongsToUser($input['threadId'], currentUserId())) {
                jsonError('Thread access denied', 403);
            }
            
            // Save user message first
            $threadModel->addMessage($input['threadId'], 'user', $input['message']);
            
            // Execute agent
            $response = $agent->execute($input['message'], $input['threadId']);
            
            // Save agent response
            $threadModel->addMessage($input['threadId'], 'assistant', $response);
            
            jsonResponse([
                'success' => true,
                'response' => $response,
                'agentId' => $agentId,
                'threadId' => $input['threadId']
            ]);
        } catch (Exception $e) {
            logger("Error executing agent: " . $e->getMessage());
            jsonError('Failed to execute agent: ' . $e->getMessage(), 500);
        }
    }
    
    public function update($agentId) {
        requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        try {
            $agent = Agent::findById($agentId);
            
            if (!$agent) {
                jsonError('Agent not found', 404);
            }
            
            // Check if agent belongs to user
            if ($agent->getUserId() != currentUserId()) {
                jsonError('Access denied', 403);
            }
            
            // Create updated agent
            $updatedAgent = new Agent(
                $input['name'] ?? $agent->getName(),
                $input['instructions'] ?? $agent->getInstructions(),
                $input['model'] ?? $agent->getModel()
            );
            
            $updatedAgent->setId($agent->getId());
            
            // Add tools
            if (isset($input['tools']) && is_array($input['tools'])) {
                foreach ($input['tools'] as $tool) {
                    $updatedAgent->addTool($tool);
                }
            } else {
                foreach ($agent->getTools() as $tool) {
                    $updatedAgent->addTool($tool);
                }
            }
            
            $updatedAgent->save();
            
            jsonResponse($updatedAgent->toArray());
        } catch (Exception $e) {
            logger("Error updating agent: " . $e->getMessage());
            jsonError('Failed to update agent', 500);
        }
    }
    
    public function destroy($agentId) {
        requireAuth();
        
        try {
            $agent = Agent::findById($agentId);
            
            if (!$agent) {
                jsonError('Agent not found', 404);
            }
            
            // Check if agent belongs to user
            if ($agent->getUserId() != currentUserId()) {
                jsonError('Access denied', 403);
            }
            
            $agent->delete();
            
            jsonResponse(['message' => 'Agent deleted successfully'], 204);
        } catch (Exception $e) {
            logger("Error deleting agent: " . $e->getMessage());
            jsonError('Failed to delete agent', 500);
        }
    }
}