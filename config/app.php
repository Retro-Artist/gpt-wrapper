<?php
/**
 * Consolidated Application Configuration
 * Single source of truth for all app settings
 */

// Load environment variables from separate file
require_once __DIR__ . '/load_env.php';

// Load environment file
$envLoaded = loadEnv(__DIR__ . '/../.env');

if (!$envLoaded) {
    error_log("WARNING: .env file could not be loaded!");
}

return [
    // OpenAI Configuration
    'openai' => [
        'api_key' => getenv('OPENAI_API_KEY') ?: '',
        'model' => getenv('OPENAI_MODEL') ?: 'gpt-4o-mini',
        'max_tokens' => (int)(getenv('OPENAI_MAX_TOKENS') ?: 1024),
        'temperature' => (float)(getenv('OPENAI_TEMPERATURE') ?: 0.7),
    ],
    
    // Database Configuration
    'database' => [
        'host' => getenv('DB_HOST') ?: 'mysql',
        'port' => (int)(getenv('DB_PORT') ?: 3306),
        'database' => getenv('DB_DATABASE') ?: 'simple_php',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: 'root_password',
        'charset' => 'utf8mb4'
    ],
    
    // Application Settings
    'app' => [
        'name' => getenv('SYSTEM_NAME') ?: 'OpenAI Webchat',
        'version' => getenv('SYSTEM_VERSION') ?: '1.0.0',
        'debug' => getenv('SYSTEM_DEBUG') === 'true',
        'session_name' => 'webchat_session'
    ]
];