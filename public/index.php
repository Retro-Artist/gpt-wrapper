<?php
// public/index.php - Main entry point for all requests

// Start session
session_start();

// Load configuration
require_once '../config/config.php';
require_once '../src/Router.php';

// Initialize router
$router = new Router();

// Web Routes (return HTML pages)
$router->addWebRoute('GET', '/', 'HomeController@index');
$router->addWebRoute('GET', '/chat', 'ChatController@index');
$router->addWebRoute('GET', '/login', 'AuthController@showLogin');
$router->addWebRoute('POST', '/login', 'AuthController@processLogin');
$router->addWebRoute('GET', '/register', 'AuthController@showRegister');
$router->addWebRoute('POST', '/register', 'AuthController@processRegister');
$router->addWebRoute('GET', '/logout', 'AuthController@logout');
$router->addWebRoute('GET', '/threads', 'ThreadController@index');
$router->addWebRoute('GET', '/agents', 'AgentController@index');

// API Routes (return JSON responses)
$router->addApiRoute('GET', '/api/threads', 'ThreadApiController@index');
$router->addApiRoute('POST', '/api/threads', 'ThreadApiController@store');
$router->addApiRoute('GET', '/api/threads/{id}', 'ThreadApiController@show');
$router->addApiRoute('PUT', '/api/threads/{id}', 'ThreadApiController@update');
$router->addApiRoute('DELETE', '/api/threads/{id}', 'ThreadApiController@destroy');
$router->addApiRoute('GET', '/api/threads/{id}/messages', 'MessageApiController@index');
$router->addApiRoute('POST', '/api/threads/{id}/messages', 'MessageApiController@store');
$router->addApiRoute('GET', '/api/agents', 'AgentApiController@index');
$router->addApiRoute('POST', '/api/agents', 'AgentApiController@store');
$router->addApiRoute('GET', '/api/agents/{id}', 'AgentApiController@show');
$router->addApiRoute('PUT', '/api/agents/{id}', 'AgentApiController@update');
$router->addApiRoute('DELETE', '/api/agents/{id}', 'AgentApiController@destroy');
$router->addApiRoute('POST', '/api/agents/{id}/run', 'AgentApiController@run');
$router->addApiRoute('GET', '/api/tools', 'AgentApiController@getAvailableTools');

// Handle the request
$router->handleRequest();