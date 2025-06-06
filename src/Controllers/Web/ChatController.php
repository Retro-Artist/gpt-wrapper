<?php
// src/Controllers/Web/ChatController.php

require_once __DIR__ . '/../../Models/Thread.php';
require_once __DIR__ . '/../../Models/Agent.php';

class ChatController {
    
    public function index() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        // Get user's threads
        $threads = Thread::getUserThreads($_SESSION['user_id']);
        
        // Get current thread (first one or create new)
        $currentThread = null;
        if (!empty($threads)) {
            $currentThread = $threads[0];
        } else {
            // Create first thread for new user
            $currentThread = Thread::create($_SESSION['user_id'], 'Welcome Chat');
            $threads = [$currentThread];
        }
        
        // Get messages for current thread
        $threadModel = new Thread();
        $messages = $threadModel->getMessages($currentThread['id']);
        
        // Get available agents for user
        $availableAgents = Agent::getUserAgents($_SESSION['user_id']);
        
        // Check if a specific agent was requested via URL parameter
        $selectedAgentId = null;
        if (isset($_GET['agent']) && is_numeric($_GET['agent'])) {
            $requestedAgent = Agent::findById($_GET['agent']);
            if ($requestedAgent && $requestedAgent->getUserId() == $_SESSION['user_id']) {
                $selectedAgentId = $_GET['agent'];
            }
        }
        
        // Load chat view
        include __DIR__ . '/../../Views/chat.php';
    }
}