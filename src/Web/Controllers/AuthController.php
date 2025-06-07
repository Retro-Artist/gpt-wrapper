<?php
/**
 * Authentication Controller
 * Handles user login, registration, and logout
 */

require_once __DIR__ . '/../Models/User.php';

class AuthController {
    
    public function showLogin() {
        requireGuest();
        
        view('login', [
            'csrf_token' => csrfToken()
        ]);
    }
    
    public function processLogin() {
        requireGuest();
        
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        logger("Login attempt for username: $username");
        
        // Validate input
        $errors = validateInput([
            'username' => $username,
            'password' => $password
        ], [
            'username' => 'required',
            'password' => 'required'
        ]);
        
        if (!empty($errors)) {
            logger("Login validation failed: " . implode(', ', $errors));
            view('login', [
                'error' => implode(', ', $errors),
                'csrf_token' => csrfToken()
            ]);
            return;
        }
        
        try {
            $userModel = new User();
            $user = $userModel->findByUsername($username);
            
            if (!$user) {
                logger("User not found: $username");
                view('login', [
                    'error' => 'Invalid username or password',
                    'csrf_token' => csrfToken()
                ]);
                return;
            }
            
            logger("User found, checking password");
            
            if ($userModel->verifyPassword($user, $password)) {
                logger("Password verified, logging in user: $username");
                
                // Login user through security system
                loginUser($user);
                
                // Update last login
                $userModel->updateLastLogin($user['id']);
                
                logger("Login successful, redirecting to chat");
                redirect('/chat');
            } else {
                logger("Password verification failed for user: $username");
                view('login', [
                    'error' => 'Invalid username or password',
                    'csrf_token' => csrfToken()
                ]);
            }
        } catch (Exception $e) {
            logger("Login error: " . $e->getMessage());
            view('login', [
                'error' => 'Login failed. Please try again.',
                'csrf_token' => csrfToken()
            ]);
        }
    }
    
    public function showRegister() {
        requireGuest();
        
        view('register', [
            'csrf_token' => csrfToken()
        ]);
    }
    
    public function processRegister() {
        requireGuest();
        
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate input
        $errors = validateInput([
            'username' => $username,
            'email' => $email,
            'password' => $password
        ], [
            'username' => 'required',
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6']
        ]);
        
        // Check password confirmation
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
        
        if (!empty($errors)) {
            view('register', [
                'error' => implode(', ', $errors),
                'csrf_token' => csrfToken()
            ]);
            return;
        }
        
        try {
            $userModel = new User();
            $user = $userModel->create($username, $email, $password);
            
            // Auto-login after registration
            loginUser($user);
            
            redirect('/chat');
            
        } catch (Exception $e) {
            view('register', [
                'error' => $e->getMessage(),
                'csrf_token' => csrfToken()
            ]);
        }
    }
    
    public function logout() {
        requireAuth();
        
        // Logout user through security system
        logoutUser();
        
        redirect('/');
    }
}