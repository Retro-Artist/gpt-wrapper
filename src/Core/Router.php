<?php
/**
 * Application Router
 * Handles web and API routes with the new structure
 */

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
        // Log the request
        Logger::getInstance()->logRequest();
        
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove trailing slash except for root
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }
        
        // Apply global security
        $this->applyGlobalSecurity();
        
        // Debug logging for API routes
        if (strpos($path, '/api/') === 0 && config('app.debug')) {
            logger("Router: Method=$method, Path=$path");
        }
        
        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $method, $path)) {
                if (strpos($path, '/api/') === 0 && config('app.debug')) {
                    logger("Router: Matched route pattern: " . $route['path']);
                    if (!empty($this->routeParameters)) {
                        logger("Router: Parameters: " . json_encode($this->routeParameters));
                    }
                }
                $this->callHandler($route);
                return;
            }
        }
        
        // Debug logging for 404s
        if (strpos($path, '/api/') === 0 && config('app.debug')) {
            logger("Router: No route found for $method $path");
        }
        
        $this->handle404();
    }
    
    private function applyGlobalSecurity() {
        // Set security headers
        security()->setSecurityHeaders();
        
        // Initialize secure session
        security()->initializeSecureSession();
        
        // Rate limiting for all requests
        checkRateLimit();
        
        // CSRF protection for POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCSRF();
        }
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
        
        if (preg_match($pattern, $path, $matches)) {
            // Store parameters for later use
            $this->routeParameters = array_slice($matches, 1);
            return true;
        }
        
        return false;
    }
    
    private function callHandler($route) {
        list($controllerName, $methodName) = explode('@', $route['handler']);
        
        // Determine controller directory based on route type
        if ($route['type'] === 'api') {
            $controllerFile = __DIR__ . "/../API/{$controllerName}.php";
        } else {
            $controllerFile = __DIR__ . "/../Web/Controllers/{$controllerName}.php";
        }
        
        if (!file_exists($controllerFile)) {
            throw new Exception("Controller file not found: {$controllerFile}");
        }
        
        require_once $controllerFile;
        
        if (!class_exists($controllerName)) {
            throw new Exception("Controller class not found: {$controllerName}");
        }
        
        $controller = new $controllerName();
        
        if (!method_exists($controller, $methodName)) {
            throw new Exception("Method not found: {$controllerName}@{$methodName}");
        }
        
        // Call method with route parameters
        if (!empty($this->routeParameters)) {
            call_user_func_array([$controller, $methodName], $this->routeParameters);
        } else {
            $controller->$methodName();
        }
    }
    
    private function handle404() {
        http_response_code(404);
        
        // Check if this is an API request
        if (isApiRequest()) {
            jsonError('Not found', 404);
        } else {
            view('error', [
                'title' => '404 - Page Not Found',
                'message' => 'The page you are looking for could not be found.'
            ]);
        }
        exit;
    }
}