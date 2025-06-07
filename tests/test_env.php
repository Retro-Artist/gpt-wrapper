<?php
/**
 * Environment Test Script - Load .env manually
 * Command: docker-compose exec app php app/test_env.php
 */

echo "🔍 Testing Environment Configuration...\n\n";

// Function to load .env file
function loadEnvFile($path = '.env') {
    echo "📁 Looking for .env file at: " . realpath($path) . "\n";
    
    if (!file_exists($path)) {
        echo "❌ .env file not found at: $path\n";
        return false;
    }
    
    echo "✅ .env file found\n";
    
    $handle = fopen($path, 'r');
    if (!$handle) {
        echo "❌ Cannot read .env file\n";
        return false;
    }
    
    $loaded = 0;
    while (($line = fgets($handle)) !== false) {
        $line = trim($line);
        
        // Skip empty lines and comments
        if (empty($line) || $line[0] == '#') continue;
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if (($value[0] == '"' && $value[-1] == '"') || ($value[0] == "'" && $value[-1] == "'")) {
                $value = substr($value, 1, -1);
            }
            
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $loaded++;
            
            // Show OpenAI key (partially hidden)
            if ($key === 'OPENAI_API_KEY') {
                echo "🔑 Found OPENAI_API_KEY: " . substr($value, 0, 7) . "..." . substr($value, -4) . "\n";
            }
        }
    }
    fclose($handle);
    
    echo "✅ Loaded $loaded environment variables\n\n";
    return true;
}

// Test 1: Check current environment
echo "1️⃣ Current environment variables:\n";
$currentApiKey = getenv('OPENAI_API_KEY');
if ($currentApiKey) {
    echo "✅ OPENAI_API_KEY found in environment\n";
} else {
    echo "❌ OPENAI_API_KEY not found in environment\n";
}

echo "\n2️⃣ Loading .env file manually...\n";
loadEnvFile('.env');

// Test 2: Check after loading .env
echo "3️⃣ Testing after loading .env:\n";
$apiKey = getenv('OPENAI_API_KEY');
if ($apiKey) {
    echo "✅ OPENAI_API_KEY now available\n";
    echo "🔑 Key length: " . strlen($apiKey) . " characters\n";
    echo "🔑 Key format check: " . (strpos($apiKey, 'sk-') === 0 ? 'Valid format' : 'Invalid format') . "\n";
    
    // Test other variables
    echo "📋 Model: " . (getenv('OPENAI_MODEL') ?: 'not set') . "\n";
    echo "📋 Max Tokens: " . (getenv('OPENAI_MAX_TOKENS') ?: 'not set') . "\n";
    echo "📋 Temperature: " . (getenv('OPENAI_TEMPERATURE') ?: 'not set') . "\n";
    echo "📋 DB Host: " . (getenv('DB_HOST') ?: 'not set') . "\n";
    
} else {
    echo "❌ OPENAI_API_KEY still not found\n";
    echo "🔍 Available environment variables:\n";
    foreach ($_ENV as $key => $value) {
        if (strpos($key, 'OPENAI') !== false || strpos($key, 'DB_') !== false) {
            echo "  $key = " . (strlen($value) > 20 ? substr($value, 0, 20) . '...' : $value) . "\n";
        }
    }
}

echo "\n4️⃣ Raw .env file contents:\n";
if (file_exists('.env')) {
    $contents = file_get_contents('.env');
    $lines = explode("\n", $contents);
    foreach ($lines as $i => $line) {
        $line = trim($line);
        if (!empty($line)) {
            echo ($i + 1) . ": $line\n";
        }
    }
} else {
    echo "❌ .env file not found\n";
}

echo "\n🎯 Test completed!\n";