<?php
/**
 * Home Controller
 * Handles the landing page
 */

class HomeController {
    
    public function index() {
        // If user is logged in, redirect to chat
        if (isAuthenticated()) {
            redirect('/chat');
        }
        
        view('home');
    }
}