<?php
// Define root path constant
define('ROOT_PATH', dirname(__FILE__));

// Ensure proper error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', ROOT_PATH . '/php_errors.log');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Add project root to include path
set_include_path(get_include_path() . PATH_SEPARATOR . ROOT_PATH);

// Load config and database
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/database.php';

// Function to ensure JSON errors
function sendJsonError($message, $code = 500, $details = []) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $message,
        'details' => $details
    ]);
    exit;
}

// Set error handler for JSON responses
set_error_handler(function($severity, $message, $file, $line) {
    error_log("PHP Error: [$severity] $message in $file:$line");
    if (error_reporting() & $severity) {
        sendJsonError("Erreur serveur: $message", 500, [
            'file' => $file,
            'line' => $line
        ]);
    }
    return true;
});