<?php
/**
 * Simple OpenAI Test - No includes, just direct testing
 * Command: docker-compose exec app php app/simple_openai_test.php
 */

echo "🧪 Simple OpenAI Test...\n\n";

// Test 1: Check environment variables
echo "1️⃣ Testing environment variables...\n";

$apiKey = getenv('OPENAI_API_KEY');
if (empty($apiKey)) {
    echo "❌ OPENAI_API_KEY not found in environment\n";
    echo "💡 Run: echo \$OPENAI_API_KEY to check\n";
    echo "💡 Make sure your .env file is loaded\n";
    exit(1);
} else {
    echo "✅ OPENAI_API_KEY found\n";
    echo "🔑 Key length: " . strlen($apiKey) . " characters\n";
    echo "🔑 Key starts with: " . substr($apiKey, 0, 7) . "...\n\n";
}

// Test 2: Test direct API call
echo "2️⃣ Testing direct OpenAI API call...\n";

$model = getenv('OPENAI_MODEL') ?: 'gpt-4o-mini';
$maxTokens = (int)(getenv('OPENAI_MAX_TOKENS') ?: 100);
$temperature = (float)(getenv('OPENAI_TEMPERATURE') ?: 0.7);

echo "📋 Using model: $model\n";
echo "📋 Max tokens: $maxTokens\n";
echo "📋 Temperature: $temperature\n\n";

// Prepare test payload
$payload = [
    'model' => $model,
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => 'Say "API test successful" if you can read this.']
    ],
    'max_tokens' => $maxTokens,
    'temperature' => $temperature
];

// Make API call
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

echo "📡 Making API call to OpenAI...\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Check for curl errors
if ($response === false) {
    echo "❌ cURL error: $curlError\n";
    exit(1);
}

// Parse response
$decoded = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ JSON parse error: " . json_last_error_msg() . "\n";
    echo "Raw response: $response\n";
    exit(1);
}

// Check HTTP status
if ($httpCode !== 200) {
    echo "❌ HTTP error: $httpCode\n";
    echo "Response: " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
    
    if (isset($decoded['error'])) {
        echo "Error details: " . $decoded['error']['message'] . "\n";
    }
    exit(1);
}

// Success!
if (isset($decoded['choices'][0]['message']['content'])) {
    $aiResponse = $decoded['choices'][0]['message']['content'];
    echo "✅ API call successful!\n";
    echo "🤖 AI Response: $aiResponse\n\n";
} else {
    echo "❌ Unexpected response format\n";
    echo "Response: " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

// Test 3: Check database connection
echo "3️⃣ Testing database connection...\n";

$dbHost = getenv('DB_HOST') ?: 'mysql';
$dbPort = getenv('DB_PORT') ?: 3306;
$dbDatabase = getenv('DB_DATABASE') ?: 'simple_php';
$dbUsername = getenv('DB_USERNAME') ?: 'root';
$dbPassword = getenv('DB_PASSWORD') ?: 'root_password';

try {
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbDatabase;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUsername, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ Database connection successful\n";
    echo "🗄️ Database: $dbDatabase on $dbHost:$dbPort\n";
    
    // Check tables
    $tables = ['users', 'threads', 'messages'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "✅ Table '$table' exists with $count records\n";
        } else {
            echo "❌ Table '$table' missing\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n🎉 All tests completed!\n";
echo "If you see ✅ for all tests, your setup is working correctly.\n";