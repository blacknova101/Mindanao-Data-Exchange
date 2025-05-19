<?php
/**
 * Mindanao Data Exchange - Error Handler
 * 
 * This file provides standardized error handling functions for the application.
 * It includes functions for logging errors, displaying user-friendly messages,
 * and redirecting users appropriately.
 */

// Define error types
define('ERROR_GENERAL', 'general');
define('ERROR_AUTH', 'auth');
define('ERROR_VALIDATION', 'validation');
define('ERROR_DATABASE', 'database');
define('ERROR_FILE', 'file');
define('ERROR_PERMISSION', 'permission');

/**
 * Log an error to the server error log
 * 
 * @param string $message Error message
 * @param string $type Error type (use predefined constants)
 * @param array $context Additional context information
 * @return void
 */
function log_error($message, $type = ERROR_GENERAL, $context = []) {
    // Format the context data as JSON
    $context_data = !empty($context) ? ' Context: ' . json_encode($context) : '';
    
    // Log to PHP error log
    error_log("[MDX-$type] $message$context_data");
}

/**
 * Handle an error, log it, and set session message
 * 
 * @param string $message User-friendly error message
 * @param string $type Error type (use predefined constants)
 * @param string $redirect_url URL to redirect to (optional)
 * @param array $context Additional context information for logging
 * @return void
 */
function handle_error($message, $type = ERROR_GENERAL, $redirect_url = null, $context = []) {
    // Log the error
    log_error($message, $type, $context);
    
    // Set session error message
    $_SESSION['error_message'] = $message;
    $_SESSION['error_type'] = $type;
    
    // Redirect if URL is provided
    if ($redirect_url) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Handle a database error
 * 
 * @param string $message User-friendly error message
 * @param mysqli $conn Database connection object
 * @param string $redirect_url URL to redirect to (optional)
 * @return void
 */
function handle_db_error($message, $conn, $redirect_url = null) {
    $context = [
        'db_error' => $conn->error,
        'db_errno' => $conn->errno
    ];
    
    handle_error($message, ERROR_DATABASE, $redirect_url, $context);
}

/**
 * Handle a validation error
 * 
 * @param string $message User-friendly error message
 * @param string $field Field that failed validation
 * @param string $redirect_url URL to redirect to (optional)
 * @return void
 */
function handle_validation_error($message, $field, $redirect_url = null) {
    $_SESSION['validation_errors'][$field] = $message;
    
    handle_error($message, ERROR_VALIDATION, $redirect_url, ['field' => $field]);
}

/**
 * Set a success message
 * 
 * @param string $message Success message
 * @param string $redirect_url URL to redirect to (optional)
 * @return void
 */
function set_success_message($message, $redirect_url = null) {
    $_SESSION['success_message'] = $message;
    
    // Redirect if URL is provided
    if ($redirect_url) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Display error message HTML
 * 
 * @return string HTML for error message
 */
function display_error_message() {
    $html = '';
    
    if (isset($_SESSION['error_message'])) {
        $error_type = isset($_SESSION['error_type']) ? $_SESSION['error_type'] : 'general';
        $html = '<div class="error-message error-' . $error_type . '">' . 
                htmlspecialchars($_SESSION['error_message']) . 
                '</div>';
        
        // Clear the message after displaying
        unset($_SESSION['error_message']);
        unset($_SESSION['error_type']);
    }
    
    return $html;
}

/**
 * Display success message HTML
 * 
 * @return string HTML for success message
 */
function display_success_message() {
    $html = '';
    
    if (isset($_SESSION['success_message'])) {
        $html = '<div class="success-message">' . 
                htmlspecialchars($_SESSION['success_message']) . 
                '</div>';
        
        // Clear the message after displaying
        unset($_SESSION['success_message']);
    }
    
    return $html;
} 