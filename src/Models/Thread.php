<?php
// src/Models/Thread.php

class Thread {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDatabaseConnection();
    }
    
    public static function getUserThreads($userId) {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT t.*, 
                   COUNT(m.id) as message_count,
                   MAX(m.created_at) as last_message_at
            FROM threads t 
            LEFT JOIN messages m ON t.id = m.thread_id 
            WHERE t.user_id = ? 
            GROUP BY t.id 
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function findById($threadId) {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("SELECT * FROM threads WHERE id = ?");
        $stmt->execute([$threadId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public static function create($userId, $title = 'New Conversation') {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            INSERT INTO threads (user_id, title) 
            VALUES (?, ?)
        ");
        $stmt->execute([$userId, $title]);
        
        return self::findById($pdo->lastInsertId());
    }
    
    public static function updateTitle($threadId, $title) {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            UPDATE threads 
            SET title = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$title, $threadId]);
        
        return self::findById($threadId);
    }
    
    public static function delete($threadId) {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("DELETE FROM threads WHERE id = ?");
        return $stmt->execute([$threadId]);
    }
    
    public function getMessages($threadId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM messages 
            WHERE thread_id = ? 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$threadId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function addMessage($threadId, $role, $content) {
        $stmt = $this->pdo->prepare("
            INSERT INTO messages (thread_id, role, content) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$threadId, $role, $content]);
        
        // Update thread timestamp
        $updateStmt = $this->pdo->prepare("
            UPDATE threads 
            SET updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $updateStmt->execute([$threadId]);
        
        return $this->pdo->lastInsertId();
    }
    
    public function belongsToUser($threadId, $userId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM threads 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$threadId, $userId]);
        return $stmt->fetchColumn() > 0;
    }
}