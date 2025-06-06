<?php
/**
 * Create Weather Agent Script
 * Command: docker-compose exec app php app/create_weather_agent.php
 */

// Load environment and start session
session_start();
$_SESSION['user_id'] = 1; // Demo user

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Models/Agent.php';

echo "🌤️ Creating Weather Agent...\n\n";

try {
    $weatherAgent = new Agent(
        "Weather Assistant",
        "You are a helpful weather assistant. When users ask about weather, use the weather tool to get current conditions and forecasts. Always provide detailed, helpful information about weather conditions, and suggest appropriate activities or clothing based on the weather. Be friendly and conversational.",
        "gpt-4o-mini"
    );
    
    $weatherAgent->addTool("Weather")->save();
    
    echo "✅ Weather Assistant created successfully!\n";
    echo "🆔 Agent ID: " . $weatherAgent->getId() . "\n";
    echo "🔧 Tools: " . implode(', ', $weatherAgent->getTools()) . "\n\n";
    
    echo "🎯 To test the agent:\n";
    echo "1. Go to http://localhost:8080/chat?agent=" . $weatherAgent->getId() . "\n";
    echo "2. Or go to /agents and click 'Test' on the Weather Assistant\n";
    echo "3. Ask: 'What's the weather in London?'\n\n";
    
    echo "🌟 The agent will now be able to use the Weather tool!\n";
    
} catch (Exception $e) {
    echo "❌ Error creating weather agent: " . $e->getMessage() . "\n";
}
?>