<?php
/**
 * Agent Model
 * Handles AI agent instances and execution
 */

class Agent {
    private $id;
    private $name;
    private $instructions;
    private $model;
    private $tools = [];
    private $userId;
    private $isActive;
    
    public function __construct($name, $instructions, $model = 'gpt-4o-mini') {
        $this->name = $name;
        $this->instructions = $instructions;
        $this->model = $model;
        $this->userId = currentUserId();
        $this->isActive = true;
    }
    
    public function addTool($toolClassName) {
        $this->tools[] = $toolClassName;
        return $this; // For method chaining
    }
    
    public function setInstructions($instructions) {
        $this->instructions = $instructions;
        return $this;
    }
    
    public function setModel($model) {
        $this->model = $model;
        return $this;
    }
    
    public function setActive($isActive) {
        $this->isActive = $isActive;
        return $this;
    }
    
    public function setId($id) {
        $this->id = $id;
        return $this;
    }
    
    public function save() {
        if ($this->id) {
            // Update existing agent
            return dbUpdate('agents', [
                'name' => $this->name,
                'instructions' => $this->instructions,
                'model' => $this->model,
                'tools' => json_encode($this->tools),
                'is_active' => $this->isActive,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$this->id]);
        } else {
            // Create new agent
            $this->id = dbInsert('agents', [
                'name' => $this->name,
                'instructions' => $this->instructions,
                'model' => $this->model,
                'tools' => json_encode($this->tools),
                'user_id' => $this->userId,
                'is_active' => $this->isActive
            ]);
        }
        
        return $this;
    }
    
    public static function findById($agentId) {
        $data = dbQueryOne("SELECT * FROM agents WHERE id = ?", [$agentId]);
        
        if (!$data) {
            return null;
        }
        
        return self::fromArray($data);
    }
    
    public static function getUserAgents($userId) {
        $results = dbQuery("
            SELECT * FROM agents 
            WHERE user_id = ? AND is_active = true 
            ORDER BY created_at DESC
        ", [$userId]);
        
        $agents = [];
        foreach ($results as $data) {
            $agents[] = self::fromArray($data);
        }
        
        return $agents;
    }
    
    private static function fromArray($data) {
        $agent = new self($data['name'], $data['instructions'], $data['model']);
        $agent->id = $data['id'];
        $agent->userId = $data['user_id'];
        $agent->isActive = $data['is_active'];
        $agent->tools = json_decode($data['tools'] ?? '[]', true);
        return $agent;
    }
    
    public function execute($message, $threadId) {
        // Load the AI system
        require_once __DIR__ . '/../../API/SystemAPI.php';
        
        // Create a run for tracking
        $run = $this->createRun($threadId);
        
        try {
            // Execute the agent through the system API
            $systemAPI = new SystemAPI();
            $response = $systemAPI->executeAgent($this, $message, $threadId);
            
            // Complete the run
            $this->completeRun($run['id'], 'completed', $response);
            
            return $response;
            
        } catch (Exception $e) {
            // Mark run as failed
            $this->completeRun($run['id'], 'failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    private function createRun($threadId) {
        $runId = dbInsert('runs', [
            'thread_id' => $threadId,
            'agent_id' => $this->id,
            'status' => 'in_progress',
            'started_at' => date('Y-m-d H:i:s')
        ]);
        
        return [
            'id' => $runId,
            'thread_id' => $threadId,
            'agent_id' => $this->id,
            'status' => 'in_progress'
        ];
    }
    
    private function completeRun($runId, $status, $metadata = null) {
        return dbUpdate('runs', [
            'status' => $status,
            'completed_at' => date('Y-m-d H:i:s'),
            'metadata' => json_encode($metadata)
        ], 'id = ?', [$runId]);
    }
    
    public function delete() {
        if ($this->id) {
            return dbUpdate('agents', ['is_active' => false], 'id = ?', [$this->id]);
        }
        return $this;
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getInstructions() { return $this->instructions; }
    public function getModel() { return $this->model; }
    public function getTools() { return $this->tools; }
    public function getUserId() { return $this->userId; }
    public function isActive() { return $this->isActive; }
    
    public function toArray() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'instructions' => $this->instructions,
            'model' => $this->model,
            'tools' => $this->tools,
            'user_id' => $this->userId,
            'is_active' => $this->isActive
        ];
    }
}