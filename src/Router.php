<?php
// src/Router.php

class Router {
    private $routes = [];
    private $routeParameters = [];
    
    public function addWebRoute($method, $path, $handler) {
        $this->routes[] = [
            'type' => 'web',
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    public function addApiRoute($method, $path, $handler) {
        $this->routes[] = [
            'type' => 'api',
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove trailing slash except for root
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }
        
        // Debug logging for API routes
        if (strpos($path, '/api/') === 0) {
            error_log("Router Debug: Method=$method, Path=$path");
        }
        
        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $method, $path)) {
                // Debug logging for matched routes
                if (strpos($path, '/api/') === 0) {
                    error_log("Router Debug: Matched route pattern: " . $route['path']);
                    if (isset($this->routeParameters)) {
                        error_log("Router Debug: Parameters: " . json_encode($this->routeParameters));
                    }
                }
                $this->callHandler($route);
                return;
            }
        }
        
        // Debug logging for 404s
        if (strpos($path, '/api/') === 0) {
            error_log("Router Debug: No route found for $method $path");
            error_log("Router Debug: Available routes:");
            foreach ($this->routes as $route) {
                if ($route['type'] === 'api') {
                    error_log("  " . $route['method'] . " " . $route['path']);
                }
            }
        }
        
        $this->handle404();
    }
    
    private function matchRoute($route, $method, $path) {
        // Check if method matches
        if ($route['method'] !== $method) {
            return false;
        }
        
        // Handle routes with parameters like /api/threads/{id}/messages
        $routePath = $route['path'];
        
        // If no parameters, do exact match
        if (strpos($routePath, '{') === false) {
            return $routePath === $path;
        }
        
        // Convert route pattern to regex
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';
        
        // Debug the pattern matching
        if (strpos($path, '/api/') === 0) {
            error_log("Router Debug: Testing pattern '$pattern' against path '$path'");
        }
        
        if (preg_match($pattern, $path, $matches)) {
            // Store parameters for later use
            $this->routeParameters = array_slice($matches, 1);
            
            // Debug the parameters
            if (strpos($path, '/api/') === 0) {
                error_log("Router Debug: Pattern matched! Parameters: " . json_encode($this->routeParameters));
            }
            
            return true;
        }
        
        return false;
    }
    
    private function callHandler($route) {
        list($controllerName, $methodName) = explode('@', $route['handler']);
        
        // Determine controller directory based on route type
        $directory = $route['type'] === 'api' ? 'Api' : 'Web';
        $controllerFile = __DIR__ . "/Controllers/{$directory}/{$controllerName}.php";
        
        if (!file_exists($controllerFile)) {
            $this->handle404("Controller file not found: {$controllerFile}");
            return;
        }
        
        require_once $controllerFile;
        
        if (!class_exists($controllerName)) {
            $this->handle404("Controller class not found: {$controllerName}");
            return;
        }
        
        $controller = new $controllerName();
        
        if (!method_exists($controller, $methodName)) {
            $this->handle404("Method not found: {$controllerName}@{$methodName}");
            return;
        }
        
        // Call method with route parameters
        if (isset($this->routeParameters) && !empty($this->routeParameters)) {
            call_user_func_array([$controller, $methodName], $this->routeParameters);
        } else {
            $controller->$methodName();
        }
    }
    
    private function handle404($message = "Page not found") {
        http_response_code(404);
        
        // Check if this is an API request
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (strpos($path, '/api/') === 0) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Not found']);
        } else {
            echo "<h1>404 - Page Not Found</h1>";
            if ($_ENV['SYSTEM_DEBUG'] ?? false) {
                echo "<p>Debug: {$message}</p>";
            }
        }
        exit;
    }
}