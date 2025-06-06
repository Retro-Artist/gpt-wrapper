<?php
/**
 * Debug Routes Script - Test if our routes work
 * Command: docker-compose exec app php app/debug_routes.php
 */

echo "🔍 Testing Route System...\n\n";

// Load environment (check if function exists first)
if (!function_exists('loadEnv')) {
    function loadEnv($path = '.env') {
        if (!file_exists($path)) return false;
        $handle = fopen($path, 'r');
        if (!$handle) return false;
        
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line) || $line[0] == '#') continue;
            
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
        fclose($handle);
        return true;
    }
}

loadEnv('.env');

// Test 1: Basic router functionality
echo "1️⃣ Testing Router Class...\n";

try {
    require_once 'src/Router.php';
    $router = new Router();
    echo "✅ Router class loaded successfully\n";
    
    // Add a test route
    $router->addApiRoute('POST', '/api/threads/{id}/messages', 'MessageApiController@store');
    echo "✅ Route added successfully\n";
    
} catch (Exception $e) {
    echo "❌ Router error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Test config loading WITHOUT including it (to avoid function conflicts)
echo "\n2️⃣ Testing Config Access...\n";

try {
    // Test if we can access config values directly
    $apiKey = getenv('OPENAI_API_KEY');
    $model = getenv('OPENAI_MODEL') ?: 'gpt-4o-mini';
    $maxTokens = getenv('OPENAI_MAX_TOKENS') ?: '1024';
    
    if (!empty($apiKey)) {
        echo "✅ OpenAI API key found in environment\n";
        echo "📋 Model: $model\n";
        echo "📋 Max Tokens: $maxTokens\n";
    } else {
        echo "❌ OpenAI API key missing from environment\n";
    }
    
} catch (Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "\n";
}

// Test 3: Test ChatService instantiation with manual config
echo "\n3️⃣ Testing ChatService (manual config)...\n";

try {
    // Create a simple test service without loading full config
    $apiKey = getenv('OPENAI_API_KEY');
    $model = getenv('OPENAI_MODEL') ?: 'gpt-4o-mini';
    
    if (empty($apiKey)) {
        echo "❌ Cannot test ChatService - API key missing\n";
    } else {
        echo "✅ API key available for ChatService\n";
        echo "🔑 Key starts with: " . substr($apiKey, 0, 7) . "...\n";
        
        // Test a direct API call without loading the ChatService class
        echo "📡 Testing direct OpenAI API call...\n";
        
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => 'Test message']
            ],
            'max_tokens' => 50
        ];
        
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo "✅ Direct OpenAI API call successful\n";
        } else {
            echo "❌ Direct OpenAI API call failed with code: $httpCode\n";
            if ($response) {
                $decoded = json_decode($response, true);
                if (isset($decoded['error'])) {
                    echo "Error: " . $decoded['error']['message'] . "\n";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ ChatService error: " . $e->getMessage() . "\n";
}

// Test 4: Check if database functions exist
echo "\n4️⃣ Testing Database Connection...\n";

try {
    $dbHost = getenv('DB_HOST') ?: 'mysql';
    $dbDatabase = getenv('DB_DATABASE') ?: 'simple_php';
    $dbUsername = getenv('DB_USERNAME') ?: 'root';
    $dbPassword = getenv('DB_PASSWORD') ?: 'root_password';
    
    $dsn = "mysql:host=$dbHost;dbname=$dbDatabase;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUsername, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ Database connection successful\n";
    
    // Check for key tables
    $stmt = $pdo->query("SHOW TABLES LIKE 'threads'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Threads table exists\n";
    } else {
        echo "❌ Threads table missing\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Test 5: Test route pattern matching
echo "\n5️⃣ Testing Route Pattern Matching...\n";

try {
    // Simulate the route matching that happens in browser
    $testPaths = [
        '/api/threads/1/messages',
        '/api/threads/123/messages',
        '/api/threads',
        '/chat'
    ];
    
    foreach ($testPaths as $path) {
        $pattern = '/api/threads/{id}/messages';
        $regexPattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
        $regexPattern = '#^' . $regexPattern . '$#';
        
        if (preg_match($regexPattern, $path, $matches)) {
            echo "✅ Path '$path' matches pattern '$pattern'\n";
            if (isset($matches[1])) {
                echo "   Extracted ID: " . $matches[1] . "\n";
            }
        } else {
            echo "❌ Path '$path' does not match pattern '$pattern'\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Route pattern error: " . $e->getMessage() . "\n";
}

echo "\n🎯 Route debugging completed!\n";
echo "\n💡 Next steps if routes work:\n";
echo "1. Check browser console for JavaScript errors\n";
echo "2. Check network tab for failed requests\n";
echo "3. Look at PHP error logs: docker-compose logs app\n";