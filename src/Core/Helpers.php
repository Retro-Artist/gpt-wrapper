<?php
/**
 * Core Helper Functions
 * Simple facades to complex functionality
 */

// ====== Security Helpers ======

function security() {
    return Security::getInstance();
}

// Authentication helpers
function requireAuth() {
    security()->requireAuth();
}

function requireGuest() {
    security()->requireGuest();
}

function isAuthenticated() {
    return security()->isAuthenticated();
}

function currentUserId() {
    return security()->getCurrentUserId();
}

function loginUser($user) {
    security()->loginUser($user);
}

function logoutUser() {
    security()->logoutUser();
}

// CSRF helpers
function csrfToken() {
    return security()->generateCSRFToken();
}

function validateCSRF($token = null) {
    return security()->validateCSRF($token);
}

function requireCSRF() {
    security()->requireCSRF();
}

// Validation helpers
function validateInput($data, $rules) {
    return security()->validateInput($data, $rules);
}

function sanitize($data) {
    return security()->sanitizeInput($data);
}

// Rate limiting
function checkRateLimit($key = null, $max = 60, $window = 3600) {
    security()->checkRateLimit($key, $max, $window);
}

// ====== Response Helpers ======

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function jsonError($message, $status = 400) {
    jsonResponse(['error' => $message], $status);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function isApiRequest() {
    return strpos($_SERVER['REQUEST_URI'], '/api/') === 0;
}

// ====== Configuration Helper ======

function config($key = null) {
    static $config = null;
    
    if ($config === null) {
        $config = require __DIR__ . '/../../config/app.php';
    }
    
    if ($key === null) {
        return $config;
    }
    
    // Support dot notation (e.g., 'openai.api_key')
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return null;
        }
        $value = $value[$k];
    }
    
    return $value;
}

// ====== Database Helpers ======

function db() {
    return Database::getInstance()->getConnection();
}

function dbQuery($sql, $params = []) {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function dbQueryOne($sql, $params = []) {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function dbInsert($table, $data) {
    $columns = implode(',', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
    
    db()->prepare($sql)->execute($data);
    return db()->lastInsertId();
}

function dbUpdate($table, $data, $where, $whereParams = []) {
    $setParts = [];
    $allParams = [];
    
    // Build SET clause with positional parameters
    foreach ($data as $column => $value) {
        $setParts[] = "{$column} = ?";
        $allParams[] = $value;
    }
    $setClause = implode(', ', $setParts);
    
    // Add WHERE parameters
    $allParams = array_merge($allParams, $whereParams);
    
    $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
    
    return db()->prepare($sql)->execute($allParams);
}

function dbDelete($table, $where, $whereParams = []) {
    $sql = "DELETE FROM {$table} WHERE {$where}";
    return db()->prepare($sql)->execute($whereParams);
}

// ====== View Helpers ======

function view($template, $data = []) {
    extract($data);
    $templatePath = __DIR__ . "/../Web/Views/{$template}.php";
    
    if (!file_exists($templatePath)) {
        throw new Exception("View template not found: {$template}");
    }
    
    include $templatePath;
}

function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function flashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// ====== Utility Helpers ======

function generateId($length = 16) {
    return bin2hex(random_bytes($length / 2));
}

function formatDate($date, $format = 'M j, Y g:i A') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

function truncate($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

function slug($text) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
}

// ====== Debug Helpers ======

function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    exit;
}

function logger($message, $level = 'INFO') {
    Logger::getInstance()->log($level, $message);
}

function benchmark($callback, $label = 'Operation') {
    $start = microtime(true);
    $result = $callback();
    $end = microtime(true);
    $duration = round(($end - $start) * 1000, 2);
    
    logger("$label completed in {$duration}ms");
    return $result;
}

// ====== Environment Helpers ======

function isDevelopment() {
    return config('app.debug') === true;
}

function isProduction() {
    return !isDevelopment();
}

function appName() {
    return config('app.name');
}

function appVersion() {
    return config('app.version');
}