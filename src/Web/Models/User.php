<?php
/**
 * User Model
 * Handles user data and authentication
 */

class User {
    
    public function findByUsername($username) {
        return dbQueryOne("SELECT * FROM users WHERE username = ?", [$username]);
    }
    
    public function findByEmail($email) {
        return dbQueryOne("SELECT * FROM users WHERE email = ?", [$email]);
    }
    
    public function findById($id) {
        return dbQueryOne("SELECT * FROM users WHERE id = ?", [$id]);
    }
    
    public function create($username, $email, $password) {
        // Check if username or email already exists
        if ($this->findByUsername($username)) {
            throw new Exception("Username already exists");
        }
        
        if ($this->findByEmail($email)) {
            throw new Exception("Email already exists");
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $userId = dbInsert('users', [
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash
        ]);
        
        return $this->findById($userId);
    }
    
    public function verifyPassword($user, $password) {
        return password_verify($password, $user['password_hash']);
    }
    
    public function updateLastLogin($userId) {
        return dbUpdate('users', 
            ['updated_at' => date('Y-m-d H:i:s')], 
            'id = ?', 
            [$userId]
        );
    }
}