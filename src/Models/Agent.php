<?php
// src/Models/Agent.php

class Agent {
    private $id;
    private $name;
    private $instructions;
    private $model;
    private $tools = [];
    private $userId;
    private $isActive;
    private $pdo;
    
    public function __construct($name, $instructions, $model = 'gpt-4o-mini') {
        $this->name = $name;
        $this->instructions = $instructions;
        $this->model = $model;
        $this->userId = $_SESSION['user_id'] ?? null;
        $this->isActive = true;
        $this->pdo = getDatabaseConnection();
    }
    
    public function addTool($toolClassName) {
        // Store just the class name for simplicity
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
            $stmt = $this->pdo->prepare("
                UPDATE agents 
                SET name = ?, instructions = ?, model = ?, tools = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([
                $this->name,
                $this->instructions, 
                $this->model,
                json_encode($this->tools),
                $this->isActive,
                $this->id
            ]);
        } else {
            // Create new agent
            $stmt = $this->pdo->prepare("
                INSERT INTO agents (name, instructions, model, tools, user_id, is_active) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $this->name,
                $this->instructions,
                $this->model,
                json_encode($this->tools),
                $this->userId,
                $this->isActive
            ]);
            $this->id = $this->pdo->lastInsertId();
        }
        
        return $this;
    }
    
    public static function findById($agentId) {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("SELECT * FROM agents WHERE id = ?");
        $stmt->execute([$agentId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return self::fromArray($data);
    }
    
    public static function getUserAgents($userId) {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM agents 
            WHERE user_id = ? AND is_active = true 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        $agents = [];
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $agents[] = self::fromArray($data);
        }
        
        return $agents;
    }
    
    public static function getAllActiveAgents() {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM agents 
            WHERE is_active = true 
            ORDER BY name ASC
        ");
        $stmt->execute();
        $agents = [];
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
        require_once __DIR__ . '/../Services/OpenAI/AgentService.php';
        
        // Create a run for tracking
        $run = $this->createRun($threadId);
        
        try {
            // Execute the agent with tools
            $agentService = new AgentService();
            $response = $agentService->executeAgent($this, $message, $threadId);
            
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
        $stmt = $this->pdo->prepare("
            INSERT INTO runs (thread_id, agent_id, status, started_at) 
            VALUES (?, ?, 'in_progress', CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$threadId, $this->id]);
        
        return [
            'id' => $this->pdo->lastInsertId(),
            'thread_id' => $threadId,
            'agent_id' => $this->id,
            'status' => 'in_progress'
        ];
    }
    
    private function completeRun($runId, $status, $metadata = null) {
        $stmt = $this->pdo->prepare("
            UPDATE runs 
            SET status = ?, completed_at = CURRENT_TIMESTAMP, metadata = ?
            WHERE id = ?
        ");
        $stmt->execute([$status, json_encode($metadata), $runId]);
    }
    
    public function delete() {
        if ($this->id) {
            $stmt = $this->pdo->prepare("UPDATE agents SET is_active = false WHERE id = ?");
            $stmt->execute([$this->id]);
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