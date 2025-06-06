<?php
// src/Controllers/Api/ThreadApiController.php

require_once __DIR__ . '/../../Models/Thread.php';

class ThreadApiController {
    
    public function index() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        try {
            // Get user's threads
            $threads = Thread::getUserThreads($_SESSION['user_id']);
            
            // Return JSON response
            header('Content-Type: application/json');
            echo json_encode($threads);
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to fetch threads']);
            error_log("Error fetching threads: " . $e->getMessage());
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
        if (!$input || !isset($input['title'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Title is required']);
            exit;
        }
        
        try {
            // Create new thread
            $thread = Thread::create($_SESSION['user_id'], $input['title']);
            
            // Return created thread
            http_response_code(201);
            header('Content-Type: application/json');
            echo json_encode($thread);
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to create thread']);
            error_log("Error creating thread: " . $e->getMessage());
        }
    }
    
    public function show($threadId) {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        try {
            $thread = Thread::findById($threadId);
            
            if (!$thread) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Thread not found']);
                exit;
            }
            
            // Check if thread belongs to user
            $threadModel = new Thread();
            if (!$threadModel->belongsToUser($threadId, $_SESSION['user_id'])) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            // Get thread with messages
            $messages = $threadModel->getMessages($threadId);
            $thread['messages'] = $messages;
            
            header('Content-Type: application/json');
            echo json_encode($thread);
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to fetch thread']);
            error_log("Error fetching thread: " . $e->getMessage());
        }
    }
    
    public function update($threadId) {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['title'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Title is required']);
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
            
            // Update thread
            $thread = Thread::updateTitle($threadId, $input['title']);
            
            header('Content-Type: application/json');
            echo json_encode($thread);
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to update thread']);
            error_log("Error updating thread: " . $e->getMessage());
        }
    }
    
    public function destroy($threadId) {
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
            
            // Delete thread
            Thread::delete($threadId);
            
            http_response_code(204);
            header('Content-Type: application/json');
            echo json_encode(['message' => 'Thread deleted successfully']);
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to delete thread']);
            error_log("Error deleting thread: " . $e->getMessage());
        }
    }
}