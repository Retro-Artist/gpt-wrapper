<?php
/**
 * OpenAI Webchat Database Migration Script
 * 
 * This script updates your existing database to support the new webchat system.
 * Run it with: docker-compose exec app php app/migrate_webchat.php
 */

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Database connection
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_DATABASE') ?: 'simple_php';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'root_password';

try {
    echo "🚀 Starting OpenAI Webchat Database Migration...\n\n";
    
    // Connect to database
    $pdo = new PDO("mysql:host={$host};dbname={$dbname}", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    echo "✅ Connected to database: {$dbname}\n\n";
    
    // Read and execute the migration SQL
    $sqlFile = __DIR__ . '/webchat_migration.sql';
    
    if (!file_exists($sqlFile)) {
        echo "❌ Migration file not found: {$sqlFile}\n";
        echo "Creating migration file...\n";
        
        $migrationSQL = "-- OpenAI Webchat Migration SQL
-- Auto-generated migration file

-- 1. Create users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL UNIQUE,
  `email` varchar(255) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_username` (`username`),
  INDEX `idx_email` (`email`)
);

-- 2. Check if conversas table exists and rename to threads
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables 
                     WHERE table_schema = DATABASE() AND table_name = 'conversas');

-- If conversas exists, rename it
SET @sql = IF(@table_exists > 0, 
              'RENAME TABLE `conversas` TO `threads`', 
              'SELECT \"Table conversas does not exist, will create threads\" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Create or update threads table
CREATE TABLE IF NOT EXISTS `threads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT 1,
  `title` varchar(255) DEFAULT 'New Conversation',
  `agent_id` int NULL COMMENT 'For future agent assignments',
  `timestamp_inicio` timestamp DEFAULT CURRENT_TIMESTAMP,
  `timestamp_fim` timestamp NULL DEFAULT NULL,
  `thread` json DEFAULT NULL COMMENT 'Legacy field from conversas table',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_timestamp` (`user_id`, `timestamp_inicio`)
);

-- 4. Add user_id column to threads if it doesn't exist
SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns 
                      WHERE table_schema = DATABASE() 
                      AND table_name = 'threads' 
                      AND column_name = 'user_id');

SET @sql = IF(@column_exists = 0, 
              'ALTER TABLE `threads` ADD COLUMN `user_id` int NOT NULL DEFAULT 1 AFTER `id`', 
              'SELECT \"user_id column already exists\" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Add title column to threads if it doesn't exist
SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns 
                      WHERE table_schema = DATABASE() 
                      AND table_name = 'threads' 
                      AND column_name = 'title');

SET @sql = IF(@column_exists = 0, 
              'ALTER TABLE `threads` ADD COLUMN `title` varchar(255) DEFAULT \"New Conversation\" AFTER `user_id`', 
              'SELECT \"title column already exists\" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. Create messages table
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `thread_id` int NOT NULL,
  `role` enum('user','assistant','system') NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_thread_created` (`thread_id`, `created_at`)
);

-- 7. Create agents table (for future use)
CREATE TABLE IF NOT EXISTS `agents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `instructions` text NOT NULL,
  `model` varchar(100) DEFAULT 'gpt-4',
  `tools` json COMMENT 'Array of available tool names',
  `user_id` int NOT NULL COMMENT 'Owner of this agent',
  `is_active` boolean DEFAULT true,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_active` (`user_id`, `is_active`)
);

-- 8. Create runs table (for future agent execution tracking)
CREATE TABLE IF NOT EXISTS `runs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `thread_id` int NOT NULL,
  `agent_id` int NOT NULL,
  `status` enum('queued','in_progress','completed','failed') DEFAULT 'queued',
  `started_at` timestamp NULL,
  `completed_at` timestamp NULL,
  `metadata` json COMMENT 'Execution details',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_thread_status` (`thread_id`, `status`),
  INDEX `idx_agent_status` (`agent_id`, `status`)
);

-- 9. Insert demo user if not exists
INSERT IGNORE INTO `users` (`id`, `username`, `email`, `password_hash`) VALUES
(1, 'demo', 'demo@example.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- 10. Update existing threads to have proper user_id
UPDATE `threads` SET `user_id` = 1 WHERE `user_id` = 0 OR `user_id` IS NULL;

-- 11. Add foreign key constraints (after data is clean)
-- Note: We'll add these manually to avoid issues with existing data

-- 12. Insert sample thread and messages if none exist
INSERT IGNORE INTO `threads` (`id`, `user_id`, `title`) VALUES
(1, 1, 'Welcome to OpenAI Webchat');

INSERT IGNORE INTO `messages` (`thread_id`, `role`, `content`) VALUES
(1, 'system', 'You are a helpful AI assistant.'),
(1, 'assistant', 'Hello! Welcome to OpenAI Webchat. I''m your AI assistant. How can I help you today?');
";
        
        file_put_contents($sqlFile, $migrationSQL);
        echo "✅ Created migration file: {$sqlFile}\n\n";
    }
    
    echo "📖 Reading migration file...\n";
    $sql = file_get_contents($sqlFile);
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    echo "🔧 Executing " . count($statements) . " SQL statements...\n\n";
    
    $successCount = 0;
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $pdo->exec($statement);
            $successCount++;
            
            // Show progress for important operations
            if (strpos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE[^`]*`([^`]+)`/', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "  ✅ Created/verified table: {$tableName}\n";
            }
            
        } catch (PDOException $e) {
            // Some errors are expected (like table already exists)
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate column') === false) {
                echo "  ⚠️  Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Migration completed successfully!\n";
    echo "📊 Executed {$successCount} statements\n\n";
    
    // Verify the setup
    echo "🔍 Verifying setup...\n";
    
    $tables = ['users', 'threads', 'messages', 'agents', 'runs'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            $count = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
            echo "  ✅ Table '{$table}' exists with {$count} records\n";
        } else {
            echo "  ❌ Table '{$table}' not found\n";
        }
    }
    
    echo "\n🎉 Database migration completed!\n";
    echo "🔑 Demo account created:\n";
    echo "    Username: demo\n";
    echo "    Password: password\n\n";
    echo "🌐 You can now access the webchat at: http://localhost:8080\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}