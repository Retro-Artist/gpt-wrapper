<?php
/**
 * Dashboard Controller
 * Handles the agent management interface
 */

require_once __DIR__ . '/../Models/Agent.php';

class DashboardController {
    
    public function index() {
        requireAuth();
        
        try {
            // Get user's agents
            $agents = Agent::getUserAgents(currentUserId());
            
            view('dashboard', ['agents' => $agents]);
        } catch (Exception $e) {
            logger("Error loading dashboard: " . $e->getMessage());
            view('error', [
                'title' => 'Dashboard Error',
                'message' => 'Failed to load dashboard. Please try again.'
            ]);
        }
    }
}