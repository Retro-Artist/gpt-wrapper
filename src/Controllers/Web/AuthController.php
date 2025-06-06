<?php
// src/Controllers/Web/AuthController.php

require_once __DIR__ . '/../../Models/User.php';

class AuthController {
    
    public function showLogin() {
        // If already logged in, redirect to chat
        if (isset($_SESSION['user_id'])) {
            header('Location: /chat');
            exit;
        }
        
        // Load login view
        include __DIR__ . '/../../Views/login.php';
    }
    
    public function processLogin() {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $error = '';
        
        if (empty($username) || empty($password)) {
            $error = 'Username and password are required';
        } else {
            try {
                $userModel = new User();
                $user = $userModel->findByUsername($username);
                
                if ($user && $userModel->verifyPassword($user, $password)) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Update last login
                    $userModel->updateLastLogin($user['id']);
                    
                    // Redirect to chat
                    header('Location: /chat');
                    exit;
                } else {
                    $error = 'Invalid username or password';
                }
            } catch (Exception $e) {
                $error = 'Login failed. Please try again.';
                error_log("Login error: " . $e->getMessage());
            }
        }
        
        // If we get here, login failed - show form with error
        include __DIR__ . '/../../Views/login.php';
    }
    
    public function showRegister() {
        // If already logged in, redirect to chat
        if (isset($_SESSION['user_id'])) {
            header('Location: /chat');
            exit;
        }
        
        // Load register view
        include __DIR__ . '/../../Views/register.php';
    }
    
    public function processRegister() {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $error = '';
        
        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'All fields are required';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } else {
            try {
                $userModel = new User();
                $user = $userModel->create($username, $email, $password);
                
                // Auto-login after registration
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                
                // Redirect to chat
                header('Location: /chat');
                exit;
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        // If we get here, registration failed - show form with error
        include __DIR__ . '/../../Views/register.php';
    }
    
    public function logout() {
        // Destroy session
        session_destroy();
        
        // Redirect to home
        header('Location: /');
        exit;
    }
}