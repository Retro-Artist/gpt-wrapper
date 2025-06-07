<?php
/**
 * Test Tool Schema Script
 * Command: docker-compose exec app php app/test_tool_schema.php
 */

echo "🧪 Testing Tool Schema Generation...\n\n";

// Load the tool classes
require_once __DIR__ . '/../src/Models/Tool.php';
require_once __DIR__ . '/../src/Tools/Weather.php';
require_once __DIR__ . '/../src/Tools/Calculator.php';
require_once __DIR__ . '/../src/Tools/WebSearch.php';

try {
    // Test Weather tool
    echo "1️⃣ Testing Weather Tool Schema...\n";
    $weather = new Weather();
    $weatherSchema = $weather->getOpenAIDefinition();
    
    echo "✅ Weather tool name: " . $weather->getName() . "\n";
    echo "✅ Weather tool description: " . $weather->getDescription() . "\n";
    echo "📋 Weather OpenAI Schema:\n";
    echo json_encode($weatherSchema, JSON_PRETTY_PRINT) . "\n\n";
    
    // Validate required fields
    if (isset($weatherSchema['function']['parameters']['required']) && 
        is_array($weatherSchema['function']['parameters']['required'])) {
        echo "✅ Required field is correctly formatted as array\n";
        echo "📋 Required parameters: " . implode(', ', $weatherSchema['function']['parameters']['required']) . "\n\n";
    } else {
        echo "❌ Required field is not properly formatted\n";
    }
    
    // Test Calculator tool
    echo "2️⃣ Testing Calculator Tool Schema...\n";
    $calculator = new Calculator();
    $calculatorSchema = $calculator->getOpenAIDefinition();
    
    echo "✅ Calculator tool name: " . $calculator->getName() . "\n";
    echo "📋 Calculator OpenAI Schema:\n";
    echo json_encode($calculatorSchema, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test WebSearch tool
    echo "3️⃣ Testing WebSearch Tool Schema...\n";
    $webSearch = new WebSearch();
    $webSearchSchema = $webSearch->getOpenAIDefinition();
    
    echo "✅ WebSearch tool name: " . $webSearch->getName() . "\n";
    echo "📋 WebSearch OpenAI Schema:\n";
    echo json_encode($webSearchSchema, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test tool execution
    echo "4️⃣ Testing Tool Execution...\n";
    
    // Test weather execution
    $weatherResult = $weather->safeExecute(['location' => 'London']);
    echo "🌤️ Weather test result:\n";
    echo json_encode($weatherResult, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test calculator execution
    $calcResult = $calculator->safeExecute(['expression' => '2 + 2']);
    echo "🧮 Calculator test result:\n";
    echo json_encode($calcResult, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "🎉 All tool schemas generated successfully!\n";
    echo "✅ The tools should now work properly with OpenAI function calling.\n";
    
} catch (Exception $e) {
    echo "❌ Error testing tool schemas: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}