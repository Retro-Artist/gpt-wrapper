<?php
// src/Models/User.php

class User {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDatabaseConnection();
    }
    
    public function findByUsername($username) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, email, password_hash) 
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([$username, $email, $passwordHash]);
        
        // Return the created user
        return $this->findById($this->pdo->lastInsertId());
    }
    
    public function verifyPassword($user, $password) {
        return password_verify($password, $user['password_hash']);
    }
    
    public function updateLastLogin($userId) {
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }
}