<?php
/**
 * Chat Controller
 * Handles the main chat interface
 */

require_once __DIR__ . '/../Models/Thread.php';
require_once __DIR__ . '/../Models/Agent.php';

class ChatController {
    
    public function index() {
        requireAuth();
        
        try {
            // Get user's threads
            $threads = Thread::getUserThreads(currentUserId());
            
            // Get current thread (first one or create new)
            $currentThread = null;
            if (!empty($threads)) {
                $currentThread = $threads[0];
            } else {
                // Create first thread for new user
                $currentThread = Thread::create(currentUserId(), 'Welcome Chat');
                $threads = [$currentThread];
            }
            
            // Get messages for current thread
            $threadModel = new Thread();
            $messages = $threadModel->getMessages($currentThread['id']);
            
            // Get available agents for user
            $availableAgents = Agent::getUserAgents(currentUserId());
            
            // Check if a specific agent was requested via URL parameter
            $selectedAgentId = null;
            if (isset($_GET['agent']) && is_numeric($_GET['agent'])) {
                $requestedAgent = Agent::findById($_GET['agent']);
                if ($requestedAgent && $requestedAgent->getUserId() == currentUserId()) {
                    $selectedAgentId = $_GET['agent'];
                }
            }
            
            view('chat', [
                'threads' => $threads,
                'currentThread' => $currentThread,
                'messages' => $messages,
                'availableAgents' => $availableAgents,
                'selectedAgentId' => $selectedAgentId
            ]);
        } catch (Exception $e) {
            logger("Error loading chat: " . $e->getMessage());
            view('error', [
                'title' => 'Chat Error',
                'message' => 'Failed to load chat interface. Please try again.'
            ]);
        }
    }
}