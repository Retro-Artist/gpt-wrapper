<?php
/**
 * Threads API
 * Handles thread management via JSON API
 */

require_once __DIR__ . '/../Web/Models/Thread.php';

class ThreadsAPI {
    
    public function index() {
        requireAuth();
        
        try {
            $threads = Thread::getUserThreads(currentUserId());
            jsonResponse($threads);
        } catch (Exception $e) {
            logger("Error fetching threads: " . $e->getMessage());
            jsonError('Failed to fetch threads', 500);
        }
    }
    
    public function store() {
        requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        $errors = validateInput($input ?? [], [
            'title' => 'required'
        ]);
        
        try {
            $thread = Thread::create(currentUserId(), $input['title']);
            jsonResponse($thread, 201);
        } catch (Exception $e) {
            logger("Error creating thread: " . $e->getMessage());
            jsonError('Failed to create thread', 500);
        }
    }
    
    public function show($threadId) {
        requireAuth();
        
        try {
            $thread = Thread::findById($threadId);
            
            if (!$thread) {
                jsonError('Thread not found', 404);
            }
            
            // Check if thread belongs to user
            $threadModel = new Thread();
            if (!$threadModel->belongsToUser($threadId, currentUserId())) {
                jsonError('Access denied', 403);
            }
            
            // Get thread with messages
            $messages = $threadModel->getMessages($threadId);
            $thread['messages'] = $messages;
            
            jsonResponse($thread);
        } catch (Exception $e) {
            logger("Error fetching thread: " . $e->getMessage());
            jsonError('Failed to fetch thread', 500);
        }
    }
    
    public function update($threadId) {
        requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        $errors = validateInput($input ?? [], [
            'title' => 'required'
        ]);
        
        try {
            // Check if thread belongs to user
            $threadModel = new Thread();
            if (!$threadModel->belongsToUser($threadId, currentUserId())) {
                jsonError('Access denied', 403);
            }
            
            $thread = Thread::updateTitle($threadId, $input['title']);
            jsonResponse($thread);
        } catch (Exception $e) {
            logger("Error updating thread: " . $e->getMessage());
            jsonError('Failed to update thread', 500);
        }
    }
    
    public function destroy($threadId) {
        requireAuth();
        
        try {
            // Check if thread belongs to user
            $threadModel = new Thread();
            if (!$threadModel->belongsToUser($threadId, currentUserId())) {
                jsonError('Access denied', 403);
            }
            
            Thread::delete($threadId);
            jsonResponse(['message' => 'Thread deleted successfully'], 204);
        } catch (Exception $e) {
            logger("Error deleting thread: " . $e->getMessage());
            jsonError('Failed to delete thread', 500);
        }
    }
    
    public function messages($threadId) {
        requireAuth();
        
        try {
            // Check if thread belongs to user
            $threadModel = new Thread();
            if (!$threadModel->belongsToUser($threadId, currentUserId())) {
                jsonError('Access denied', 403);
            }
            
            $messages = $threadModel->getMessages($threadId);
            jsonResponse($messages);
        } catch (Exception $e) {
            logger("Error fetching messages: " . $e->getMessage());
            jsonError('Failed to fetch messages', 500);
        }
    }
    
    public function addMessage($threadId) {
        requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        $errors = validateInput($input ?? [], [
            'message' => 'required'
        ]);
        
        $userMessage = trim($input['message']);
        if (empty($userMessage)) {
            jsonError('Message cannot be empty', 400);
        }
        
        try {
            // Check if thread belongs to user
            $threadModel = new Thread();
            if (!$threadModel->belongsToUser($threadId, currentUserId())) {
                jsonError('Access denied', 403);
            }
            
            // Save user message
            $threadModel->addMessage($threadId, 'user', $userMessage);
            
            // Get AI response through SystemAPI
            require_once __DIR__ . '/SystemAPI.php';
            $systemAPI = new SystemAPI();
            $aiResponse = $systemAPI->processMessage($threadId, $userMessage);
            
            // Save AI response
            $threadModel->addMessage($threadId, 'assistant', $aiResponse);
            
            // Return response
            jsonResponse([
                'success' => true,
                'response' => $aiResponse,
                'threadId' => $threadId
            ]);
            
        } catch (Exception $e) {
            logger("Error sending message: " . $e->getMessage());
            jsonError('Failed to send message: ' . $e->getMessage(), 500);
        }
    }
}