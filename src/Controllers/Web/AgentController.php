<?php
// src/Controllers/Web/AgentController.php

require_once __DIR__ . '/../../Models/Agent.php';

class AgentController {
    
    public function index() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        // Get user's agents
        $agents = Agent::getUserAgents($_SESSION['user_id']);
        
        // Load agent management view
        include __DIR__ . '/../../Views/agents.php';
    }
    
    public function create() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        // Load create agent view
        include __DIR__ . '/../../Views/create_agent.php';
    }
}