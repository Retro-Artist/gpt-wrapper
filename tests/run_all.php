<?php
/**
 * Consolidated Test Runner
 * Runs all tests in sequence with clear output
 * Command: docker-compose exec app php tests/run_all.php
 */

echo "🧪 Running All Tests for OpenAI Webchat\n";
echo str_repeat("=", 60) . "\n\n";

$testFiles = [
    'test_env.php' => 'Environment Configuration Test',
    'test_openai.php' => 'OpenAI API Integration Test', 
    'test_tool_schema.php' => 'Tool Schema Generation Test',
    'debug_routes.php' => 'Router System Test'
];

$passed = 0;
$failed = 0;

foreach ($testFiles as $testFile => $description) {
    if (file_exists(__DIR__ . "/$testFile")) {
        echo "▶️ Running: $description\n";
        echo "📄 File: $testFile\n";
        echo str_repeat("-", 50) . "\n";
        
        $startTime = microtime(true);
        
        // Capture output
        ob_start();
        $success = true;
        
        try {
            include __DIR__ . "/$testFile";
        } catch (Exception $e) {
            echo "❌ Test failed with exception: " . $e->getMessage() . "\n";
            $success = false;
        } catch (Error $e) {
            echo "❌ Test failed with error: " . $e->getMessage() . "\n";
            $success = false;
        }
        
        $output = ob_get_clean();
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        echo $output;
        
        if ($success && !strpos($output, '❌')) {
            echo "✅ Test completed successfully in {$duration}ms\n";
            $passed++;
        } else {
            echo "❌ Test completed with errors in {$duration}ms\n";
            $failed++;
        }
        
        echo "\n" . str_repeat("=", 60) . "\n\n";
    } else {
        echo "⚠️ Test file not found: $testFile\n\n";
        $failed++;
    }
}

// Summary
echo "📊 Test Summary:\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";
echo "📈 Total: " . ($passed + $failed) . "\n\n";

if ($failed === 0) {
    echo "🎉 All tests passed! Your OpenAI Webchat is ready to go!\n";
    echo "🌐 Access your app at: http://localhost:8080\n";
    echo "👤 Demo login: username='demo', password='password'\n";
} else {
    echo "⚠️ Some tests failed. Please check the output above.\n";
    echo "💡 Common issues:\n";
    echo "   - Missing .env file or invalid OpenAI API key\n";
    echo "   - Database connection issues\n";
    echo "   - Missing dependencies\n";
}

echo "\n🔧 Useful commands:\n";
echo "   docker-compose logs app    # Check application logs\n";
echo "   docker-compose exec app php app/migrate.php    # Run database migration\n";
echo "   docker-compose restart     # Restart all services\n";