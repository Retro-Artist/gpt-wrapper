<?php
/**
 * Test OpenAI Configuration Script
 * 
 * Run this to test if your OpenAI API setup is working
 * Command: docker-compose exec app php app/test_openai.php
 */

echo "🧪 Testing OpenAI Configuration...\n\n";

// Load configuration ONCE
if (!function_exists('getDatabaseConnection')) {
    require_once __DIR__ . '/../config/config.php';
}

// Check if config loads
try {
    $config = include __DIR__ . '/../config/config.php';
    echo "✅ Configuration loaded successfully\n";
    
    // Check API key
    if (empty($config['api_key'])) {
        echo "❌ OpenAI API key is empty or not set\n";
        echo "💡 Check your .env file and make sure OPENAI_API_KEY is set\n";
        exit(1);
    } else {
        echo "✅ OpenAI API key is set (length: " . strlen($config['api_key']) . " characters)\n";
        echo "🔑 Key starts with: " . substr($config['api_key'], 0, 7) . "...\n";
    }
    
    // Check other config values
    echo "📋 Model: " . $config['model'] . "\n";
    echo "📋 Max Tokens: " . $config['max_tokens'] . "\n";
    echo "📋 Temperature: " . $config['temperature'] . "\n\n";
    
} catch (Exception $e) {
    echo "❌ Configuration error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test environment variables directly
echo "🔍 Testing environment variables...\n";
$envApiKey = getenv('OPENAI_API_KEY');
if (empty($envApiKey)) {
    echo "⚠️  OPENAI_API_KEY environment variable is not set\n";
    echo "💡 Make sure your .env file has OPENAI_API_KEY=your_key_here\n";
} else {
    echo "✅ OPENAI_API_KEY environment variable is set\n";
    echo "🔑 Key starts with: " . substr($envApiKey, 0, 7) . "...\n";
}

// Test ChatService
echo "\n🤖 Testing ChatService...\n";

try {
    if (!class_exists('ChatService')) {
        require_once __DIR__ . '/../src/Services/OpenAI/ChatService.php';
    }
    
    $chatService = new ChatService();
    echo "✅ ChatService instantiated successfully\n";
    
    // Test a simple API call
    echo "📡 Testing API call...\n";
    
    $response = $chatService->sendMessage("Hello! Just testing the connection. Please respond with 'Connection successful!'");
    
    echo "✅ API call successful!\n";
    echo "🤖 Response: " . $response . "\n\n";
    
    echo "🎉 OpenAI configuration is working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ ChatService error: " . $e->getMessage() . "\n";
    echo "\n🔍 Common issues:\n";
    echo "   1. Invalid API key\n";
    echo "   2. API key quota exceeded\n";
    echo "   3. Network connection issues\n";
    echo "   4. API key doesn't have access to the specified model\n\n";
    
    // Show more detailed error info
    echo "🔍 Detailed error info:\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n\n";
    exit(1);
}

// Test database connection
echo "🗄️  Testing database connection...\n";

try {
    $pdo = getDatabaseConnection();
    if ($pdo) {
        echo "✅ Database connection successful\n";
        
        // Test if tables exist
        $tables = ['users', 'threads', 'messages', 'agents'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() > 0) {
                echo "✅ Table '{$table}' exists\n";
            } else {
                echo "❌ Table '{$table}' missing - run migration script\n";
            }
        }
    } else {
        echo "❌ Database connection failed\n";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n🎯 Test completed!\n";