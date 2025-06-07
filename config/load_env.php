<?php
/**
 * Simple environment variable loader
 * Keeps the original clean separation
 */

if (!function_exists('loadEnv')) {
    function loadEnv($path = '.env') {
        // Check if file exists
        if (!file_exists($path)) {
            return false;
        }
        
        // Read file line by line
        $handle = fopen($path, 'r');
        if (!$handle) return false;
        
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line) || $line[0] == '#') continue;
            
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (($value[0] == '"' && $value[-1] == '"') || ($value[0] == "'" && $value[-1] == "'")) {
                    $value = substr($value, 1, -1);
                }
                
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
        fclose($handle);
        
        return true;
    }
}