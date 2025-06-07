<?php
/**
 * Thread Model
 * Handles chat threads and messages
 */

class Thread {
    
    public static function getUserThreads($userId) {
        return dbQuery("
            SELECT t.*, 
                   COUNT(m.id) as message_count,
                   MAX(m.created_at) as last_message_at
            FROM threads t 
            LEFT JOIN messages m ON t.id = m.thread_id 
            WHERE t.user_id = ? 
            GROUP BY t.id 
            ORDER BY t.created_at DESC
        ", [$userId]);
    }
    
    public static function findById($threadId) {
        return dbQueryOne("SELECT * FROM threads WHERE id = ?", [$threadId]);
    }
    
    public static function create($userId, $title = 'New Conversation') {
        $threadId = dbInsert('threads', [
            'user_id' => $userId,
            'title' => $title
        ]);
        
        return self::findById($threadId);
    }
    
    public static function updateTitle($threadId, $title) {
        dbUpdate('threads', 
            ['title' => $title, 'updated_at' => date('Y-m-d H:i:s')], 
            'id = ?', 
            [$threadId]
        );
        
        return self::findById($threadId);
    }
    
    public static function delete($threadId) {
        return dbDelete('threads', 'id = ?', [$threadId]);
    }
    
    public function getMessages($threadId) {
        return dbQuery("
            SELECT * FROM messages 
            WHERE thread_id = ? 
            ORDER BY created_at ASC
        ", [$threadId]);
    }
    
    public function addMessage($threadId, $role, $content) {
        $messageId = dbInsert('messages', [
            'thread_id' => $threadId,
            'role' => $role,
            'content' => $content
        ]);
        
        // Update thread timestamp
        dbUpdate('threads', 
            ['updated_at' => date('Y-m-d H:i:s')], 
            'id = ?', 
            [$threadId]
        );
        
        return $messageId;
    }
    
    public function belongsToUser($threadId, $userId) {
        $result = dbQueryOne("
            SELECT COUNT(*) as count FROM threads 
            WHERE id = ? AND user_id = ?
        ", [$threadId, $userId]);
        
        return $result['count'] > 0;
    }
}