<?php
/**
 * Application Entry Point
 * Updated for the new flattened structure
 */

// Load Core infrastructure first
require_once '../src/Core/Helpers.php';
require_once '../src/Core/Security.php';
require_once '../src/Core/Database.php';
require_once '../src/Core/Logger.php';
require_once '../src/Core/Router.php';

// Start session
session_start();

// Global error handler
set_exception_handler(function($exception) {
    Logger::getInstance()->logError($exception);
    
    if (config('app.debug')) {
        if (isApiRequest()) {
            jsonError('Error: ' . $exception->getMessage(), 500);
        } else {
            echo "<h1>Application Error</h1>";
            echo "<p><strong>Message:</strong> " . escape($exception->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . escape($exception->getFile()) . "</p>";
            echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
            echo "<pre>" . escape($exception->getTraceAsString()) . "</pre>";
        }
    } else {
        if (isApiRequest()) {
            jsonError('Internal server error', 500);
        } else {
            view('error', [
                'title' => '500 - Internal Server Error',
                'message' => 'Something went wrong. Please try again later.'
            ]);
        }
    }
    exit;
});

// Initialize router
$router = new Router();

// ====== Web Routes (return HTML pages) ======
$router->addWebRoute('GET', '/', 'HomeController@index');
$router->addWebRoute('GET', '/chat', 'ChatController@index');
$router->addWebRoute('GET', '/dashboard', 'DashboardController@index');

// Auth routes
$router->addWebRoute('GET', '/login', 'AuthController@showLogin');
$router->addWebRoute('POST', '/login', 'AuthController@processLogin');
$router->addWebRoute('GET', '/register', 'AuthController@showRegister');
$router->addWebRoute('POST', '/register', 'AuthController@processRegister');
$router->addWebRoute('GET', '/logout', 'AuthController@logout');

// ====== API Routes (return JSON responses) ======

// System endpoints
$router->addApiRoute('GET', '/api/health', 'SystemAPI@health');
$router->addApiRoute('GET', '/api/stats', 'SystemAPI@stats');

// Thread management
$router->addApiRoute('GET', '/api/threads', 'ThreadsAPI@index');
$router->addApiRoute('POST', '/api/threads', 'ThreadsAPI@store');
$router->addApiRoute('GET', '/api/threads/{id}', 'ThreadsAPI@show');
$router->addApiRoute('PUT', '/api/threads/{id}', 'ThreadsAPI@update');
$router->addApiRoute('DELETE', '/api/threads/{id}', 'ThreadsAPI@destroy');

// Message handling
$router->addApiRoute('GET', '/api/threads/{id}/messages', 'ThreadsAPI@messages');
$router->addApiRoute('POST', '/api/threads/{id}/messages', 'ThreadsAPI@addMessage');

// Agent management
$router->addApiRoute('GET', '/api/agents', 'AgentsAPI@index');
$router->addApiRoute('POST', '/api/agents', 'AgentsAPI@store');
$router->addApiRoute('GET', '/api/agents/{id}', 'AgentsAPI@show');
$router->addApiRoute('PUT', '/api/agents/{id}', 'AgentsAPI@update');
$router->addApiRoute('DELETE', '/api/agents/{id}', 'AgentsAPI@destroy');
$router->addApiRoute('POST', '/api/agents/{id}/run', 'AgentsAPI@run');

// Tool management
$router->addApiRoute('GET', '/api/tools', 'ToolsAPI@index');
$router->addApiRoute('GET', '/api/tools/{name}', 'ToolsAPI@show');
$router->addApiRoute('POST', '/api/tools/{name}/execute', 'ToolsAPI@execute');
$router->addApiRoute('POST', '/api/tools/{name}/validate', 'ToolsAPI@validate');

// Handle the request
$router->handleRequest();