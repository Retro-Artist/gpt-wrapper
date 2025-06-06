<?php
// src/Controllers/Web/HomeController.php

class HomeController {
    
    public function index() {
        // If user is logged in, redirect to chat
        if (isset($_SESSION['user_id'])) {
            header('Location: /chat');
            exit;
        }
        
        // Load home/landing page
        include __DIR__ . '/../../Views/home.php';
    }
}