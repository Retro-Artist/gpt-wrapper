<?php
/**
 * Core Security Manager
 * Centralized handling of authentication, CSRF, rate limiting, and validation
 */

class Security {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // ====== Authentication ======
    
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            if ($this->isApiRequest()) {
                $this->jsonError('Unauthorized', 401);
            } else {
                $this->redirect('/login');
            }
        }
    }
    
    public function requireGuest() {
        if ($this->isAuthenticated()) {
            $this->redirect('/chat');
        }
    }
    
    public function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public function loginUser($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['login_time'] = time();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        Logger::getInstance()->info("User logged in: " . $user['username']);
    }
    
    public function logoutUser() {
        $username = $_SESSION['username'] ?? 'unknown';
        
        // Clear session data
        session_unset();
        session_destroy();
        
        // Start new session
        session_start();
        
        Logger::getInstance()->info("User logged out: $username");
    }
    
    // ====== CSRF Protection ======
    
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public function validateCSRF($token = null) {
        // Get token from various sources
        $token = $token ?? 
                 $_POST['csrf_token'] ?? 
                 $_SERVER['HTTP_X_CSRF_TOKEN'] ?? 
                 '';
        
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public function requireCSRF() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$this->validateCSRF()) {
            Logger::getInstance()->warning("CSRF token validation failed for: " . $_SERVER['REQUEST_URI']);
            
            if ($this->isApiRequest()) {
                $this->jsonError('CSRF token mismatch', 403);
            } else {
                $this->redirect('/', 'Security error. Please try again.');
            }
        }
    }
    
    // ====== Rate Limiting ======
    
    public function checkRateLimit($key = null, $maxAttempts = 60, $timeWindow = 3600) {
        $key = $key ?? $this->getRateLimitKey();
        $cacheKey = "rate_limit:" . md5($key);
        
        $attempts = $this->getRateLimitAttempts($cacheKey);
        
        if ($attempts >= $maxAttempts) {
            Logger::getInstance()->warning("Rate limit exceeded for: $key");
            
            if ($this->isApiRequest()) {
                $this->jsonError('Rate limit exceeded. Please try again later.', 429);
            } else {
                $this->redirect('/', 'Too many requests. Please try again later.');
            }
        }
        
        $this->incrementRateLimit($cacheKey, $timeWindow);
    }
    
    private function getRateLimitKey() {
        // Combine IP and user agent for better identification
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100);
        return $ip . ':' . md5($userAgent);
    }
    
    // ====== Input Validation & Sanitization ======
    
    public function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    public function validateInput($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $fieldRules = is_array($fieldRules) ? $fieldRules : [$fieldRules];
            $value = $data[$field] ?? '';
            
            foreach ($fieldRules as $rule) {
                $error = $this->validateRule($field, $value, $rule);
                if ($error) {
                    $errors[] = $error;
                    break; // Stop at first error for this field
                }
            }
        }
        
        if (!empty($errors)) {
            if ($this->isApiRequest()) {
                $this->jsonError(implode(', ', $errors), 400);
            }
        }
        
        return $errors;
    }
    
    private function validateRule($field, $value, $rule) {
        switch ($rule) {
            case 'required':
                return empty($value) ? ucfirst($field) . " is required" : null;
                
            case 'email':
                return !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL) 
                    ? "Invalid email format" : null;
                    
            case 'numeric':
                return !empty($value) && !is_numeric($value)
                    ? ucfirst($field) . " must be a number" : null;
                    
            case 'boolean':
                return !empty($value) && !in_array($value, ['true', 'false', '1', '0', 1, 0, true, false])
                    ? ucfirst($field) . " must be true or false" : null;
                    
            default:
                if (strpos($rule, 'min:') === 0) {
                    $min = (int)substr($rule, 4);
                    return !empty($value) && strlen($value) < $min 
                        ? ucfirst($field) . " must be at least {$min} characters" : null;
                }
                
                if (strpos($rule, 'max:') === 0) {
                    $max = (int)substr($rule, 4);
                    return !empty($value) && strlen($value) > $max 
                        ? ucfirst($field) . " must be no more than {$max} characters" : null;
                }
                
                if (strpos($rule, 'in:') === 0) {
                    $values = explode(',', substr($rule, 3));
                    return !empty($value) && !in_array($value, $values)
                        ? ucfirst($field) . " must be one of: " . implode(', ', $values) : null;
                }
                
                return null;
        }
    }
    
    // ====== Security Headers ======
    
    public function setSecurityHeaders() {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Content Security Policy (basic)
        if (!$this->isApiRequest()) {
            header("Content-Security-Policy: default-src 'self' 'unsafe-inline' cdn.tailwindcss.com https://cdnjs.cloudflare.com");
        }
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Remove server signature
        header('Server: OpenAI-Webchat');
    }
    
    // ====== Password Security ======
    
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    // ====== IP & User Agent Utilities ======
    
    public function getUserIP() {
        // Check for various proxy headers
        $ipKeys = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (from proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    public function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }
    
    // ====== Helper Methods ======
    
    private function isApiRequest() {
        return strpos($_SERVER['REQUEST_URI'], '/api/') === 0;
    }
    
    private function jsonError($message, $status = 400) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
    
    private function redirect($url, $message = null) {
        if ($message) {
            $_SESSION['flash_message'] = $message;
        }
        header("Location: $url");
        exit;
    }
    
    // ====== Rate Limiting Storage ======
    
    private function getRateLimitAttempts($key) {
        $file = sys_get_temp_dir() . "/rate_limit_" . md5($key);
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && $data['expires'] > time()) {
                return $data['attempts'];
            }
        }
        return 0;
    }
    
    private function incrementRateLimit($key, $timeWindow) {
        $file = sys_get_temp_dir() . "/rate_limit_" . md5($key);
        $attempts = $this->getRateLimitAttempts($key) + 1;
        $data = [
            'attempts' => $attempts,
            'expires' => time() + $timeWindow
        ];
        file_put_contents($file, json_encode($data));
    }
    
    // ====== Session Security ======
    
    public function initializeSecureSession() {
        // Configure secure session settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS in production
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Lax');
        
        // Regenerate session ID periodically
        if (isset($_SESSION['last_regeneration'])) {
            $lastRegen = $_SESSION['last_regeneration'];
            if (time() - $lastRegen > 300) { // 5 minutes
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        } else {
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    // ====== Security Auditing ======
    
    public function logSecurityEvent($event, $details = []) {
        $logData = [
            'event' => $event,
            'user_id' => $this->getCurrentUserId(),
            'ip' => $this->getUserIP(),
            'user_agent' => substr($this->getUserAgent(), 0, 200),
            'timestamp' => date('Y-m-d H:i:s'),
            'details' => $details
        ];
        
        Logger::getInstance()->warning("Security Event: $event", $logData);
    }
    
    public function logLoginAttempt($username, $success = false) {
        $this->logSecurityEvent('login_attempt', [
            'username' => $username,
            'success' => $success
        ]);
    }
    
    public function logFailedAuth($reason = 'invalid_credentials') {
        $this->logSecurityEvent('auth_failure', [
            'reason' => $reason
        ]);
    }
    
    // ====== Content Security ======
    
    public function sanitizeFilename($filename) {
        // Remove any path traversal attempts
        $filename = basename($filename);
        
        // Remove or replace dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Limit length
        return substr($filename, 0, 255);
    }
    
    public function isAllowedFileType($filename, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'doc', 'docx']) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $allowedTypes);
    }
    
    // Prevent cloning and unserialization
    private function __clone() {}
    private function __wakeup() {}
}