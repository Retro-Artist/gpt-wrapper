<?php
/**
 * Run Model
 * Handles agent execution tracking
 */

class Run {
    
    public static function create($threadId, $agentId) {
        $runId = dbInsert('runs', [
            'thread_id' => $threadId,
            'agent_id' => $agentId,
            'status' => 'queued',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return self::findById($runId);
    }
    
    public static function findById($runId) {
        return dbQueryOne("SELECT * FROM runs WHERE id = ?", [$runId]);
    }
    
    public static function getByThread($threadId) {
        return dbQuery("
            SELECT r.*, a.name as agent_name 
            FROM runs r 
            JOIN agents a ON r.agent_id = a.id 
            WHERE r.thread_id = ? 
            ORDER BY r.created_at DESC
        ", [$threadId]);
    }
    
    public static function getByAgent($agentId) {
        return dbQuery("
            SELECT r.*, t.title as thread_title 
            FROM runs r 
            JOIN threads t ON r.thread_id = t.id 
            WHERE r.agent_id = ? 
            ORDER BY r.created_at DESC
        ", [$agentId]);
    }
    
    public static function updateStatus($runId, $status, $metadata = null) {
        $updateData = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($status === 'in_progress' && !self::findById($runId)['started_at']) {
            $updateData['started_at'] = date('Y-m-d H:i:s');
        }
        
        if (in_array($status, ['completed', 'failed', 'cancelled'])) {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
        }
        
        if ($metadata !== null) {
            $updateData['metadata'] = json_encode($metadata);
        }
        
        dbUpdate('runs', $updateData, 'id = ?', [$runId]);
        
        return self::findById($runId);
    }
    
    public static function getStats($userId = null) {
        $whereClause = $userId ? "WHERE t.user_id = ?" : "";
        $params = $userId ? [$userId] : [];
        
        return dbQueryOne("
            SELECT 
                COUNT(*) as total_runs,
                SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed_runs,
                SUM(CASE WHEN r.status = 'failed' THEN 1 ELSE 0 END) as failed_runs,
                SUM(CASE WHEN r.status = 'in_progress' THEN 1 ELSE 0 END) as running_runs,
                AVG(CASE 
                    WHEN r.started_at IS NOT NULL AND r.completed_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(SECOND, r.started_at, r.completed_at) 
                    ELSE NULL 
                END) as avg_duration_seconds
            FROM runs r
            JOIN threads t ON r.thread_id = t.id
            {$whereClause}
        ", $params);
    }
    
    public static function cancel($runId) {
        return self::updateStatus($runId, 'cancelled');
    }
    
    public static function cleanup($olderThanDays = 30) {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$olderThanDays} days"));
        
        return dbDelete('runs', 
            "status IN ('completed', 'failed', 'cancelled') AND completed_at < ?", 
            [$cutoffDate]
        );
    }
}