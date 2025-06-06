<?php
// src/Controllers/Api/MessageApiController.php

require_once __DIR__ . '/../../Models/Thread.php';
require_once __DIR__ . '/../../Services/OpenAI/ChatService.php';

class MessageApiController {
    
    public function index($threadId) {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        try {
            // Check if thread belongs to user
            $threadModel = new Thread();
            if (!$threadModel->belongsToUser($threadId, $_SESSION['user_id'])) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            // Get messages
            $messages = $threadModel->getMessages($threadId);
            
            header('Content-Type: application/json');
            echo json_encode($messages);
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to fetch messages']);
            error_log("Error fetching messages: " . $e->getMessage());
        }
    }
    
    public function store($threadId) {
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
        if (!$input || !isset($input['message']) || empty(trim($input['message']))) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Message is required']);
            exit;
        }
        
        try {
            // Check if thread belongs to user
            $threadModel = new Thread();
            if (!$threadModel->belongsToUser($threadId, $_SESSION['user_id'])) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            $userMessage = trim($input['message']);
            
            // Save user message
            $threadModel->addMessage($threadId, 'user', $userMessage);
            
            // Get conversation history for context
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
            $chatService = new ChatService();
            $aiResponse = $chatService->sendMessage($userMessage, $conversationHistory);
            
            // Save AI response
            $threadModel->addMessage($threadId, 'assistant', $aiResponse);
            
            // Return response
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'response' => $aiResponse,
                'threadId' => $threadId
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to send message: ' . $e->getMessage()]);
            error_log("Error sending message: " . $e->getMessage());
        }
    }
}