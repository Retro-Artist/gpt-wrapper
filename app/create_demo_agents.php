<?php
/**
 * Demo Agent Creation Script
 * 
 * This script demonstrates how easy it is to create agents with our clean system
 * Run it with: docker-compose exec app php app/create_demo_agents.php
 */

// Load configuration and models
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Models/Agent.php';

// Start session to simulate a user
session_start();
$_SESSION['user_id'] = 1; // Demo user

echo "🤖 Creating Demo Agents...\n\n";

try {
    // 1. Simple Chat Agent
    echo "Creating Chat Assistant...\n";
    $chatBot = new Agent(
        "Friendly Assistant", 
        "You are a helpful and friendly AI assistant. Always be polite, informative, and engaging. Help users with their questions and provide clear, helpful responses."
    );
    $chatBot->save();
    echo "✅ Chat Assistant created with ID: " . $chatBot->getId() . "\n\n";

    // 2. Code Assistant with Tools
    echo "Creating Code Assistant...\n";
    $codeBot = new Agent(
        "Senior Developer", 
        "You are a senior software developer with expertise in PHP, JavaScript, Python, and modern web development. You help with coding problems, code review, debugging, and best practices. Always provide clean, well-commented code examples.",
        "gpt-4o"
    );
    $codeBot->addTool("Calculator")
            ->addTool("WebSearch")
            ->save();
    echo "✅ Code Assistant created with ID: " . $codeBot->getId() . " (with 2 tools)\n\n";

    // 3. Research Agent with Tools
    echo "Creating Research Analyst...\n";
    $researcher = new Agent(
        "Research Analyst", 
        "You are a thorough research analyst who helps users find information, analyze data, and provide insights. You're skilled at breaking down complex topics, fact-checking, and presenting information clearly. Always cite your sources when possible.",
        "gpt-4o-mini"
    );
    $researcher->addTool("WebSearch")
              ->addTool("Calculator")
              ->addTool("Weather")
              ->save();
    echo "✅ Research Analyst created with ID: " . $researcher->getId() . " (with 3 tools)\n\n";

    // 4. Weather Assistant
    echo "Creating Weather Assistant...\n";
    $weatherBot = new Agent(
        "Weather Assistant",
        "You are a helpful weather assistant. You provide current weather information, forecasts, and weather-related advice. Always be informative about weather conditions and suggest appropriate activities or precautions based on the weather."
    );
    $weatherBot->addTool("Weather")
              ->save();
    echo "✅ Weather Assistant created with ID: " . $weatherBot->getId() . " (with 1 tool)\n\n";

    // 5. All-Purpose Agent with All Tools
    echo "Creating Super Agent...\n";
    $superAgent = new Agent(
        "Super Agent",
        "You are a versatile AI assistant with access to multiple tools. You can help with calculations, web research, weather information, and general questions. Choose the appropriate tools based on what the user needs. Always explain what tools you're using and why."
    );
    $superAgent->addTool("Calculator")
              ->addTool("WebSearch")
              ->addTool("Weather")
              ->save();
    echo "✅ Super Agent created with ID: " . $superAgent->getId() . " (with 3 tools)\n\n";

    echo "🎉 Demo agents created successfully!\n";
    echo "💡 You can now test them in the web interface at /agents\n\n";
    
    // Show the simple creation syntax
    echo "📝 Here's how simple it was to create these agents:\n\n";
    
    echo "// Basic chat agent\n";
    echo '$chatBot = new Agent("Friendly Assistant", "You are helpful...")' . "\n";
    echo '$chatBot->save();' . "\n\n";
    
    echo "// Agent with tools\n";
    echo '$weatherBot = new Agent("Weather Assistant", "You provide weather info...", "gpt-4o")' . "\n";
    echo '$weatherBot->addTool("Weather")' . "\n";
    echo '          ->save();' . "\n\n";
    
    echo "// Multi-tool agent\n";
    echo '$superAgent = new Agent("Super Agent", "You are versatile...", "gpt-4o")' . "\n";
    echo '$superAgent->addTool("Calculator")' . "\n";
    echo '           ->addTool("WebSearch")' . "\n";
    echo '           ->addTool("Weather")' . "\n";
    echo '           ->save();' . "\n\n";
    
    echo "🚀 That's it! Clean, simple, and powerful.\n";

} catch (Exception $e) {
    echo "❌ Error creating demo agents: " . $e->getMessage() . "\n";
    exit(1);
}