<?php
/**
 * Mindanao Data Exchange - Path Handler
 * 
 * This file provides standardized path handling functions for the application.
 * It includes functions for resolving file paths, creating directories,
 * and validating file paths.
 */

// Define the application root directory
define('APP_ROOT', realpath(dirname(__FILE__) . '/..'));
define('UPLOADS_DIR', APP_ROOT . '/uploads');
define('TEMP_DIR', APP_ROOT . '/temp');

/**
 * Get the absolute path from a relative path
 * 
 * @param string $relative_path Path relative to application root
 * @return string Absolute path
 */
function get_absolute_path($relative_path) {
    // Remove any leading slash to ensure proper concatenation
    $relative_path = ltrim($relative_path, '/\\');
    return APP_ROOT . '/' . $relative_path;
}

/**
 * Get a path relative to the application root
 * 
 * @param string $absolute_path Absolute file path
 * @return string Path relative to application root
 */
function get_relative_path($absolute_path) {
    return str_replace(APP_ROOT . '/', '', $absolute_path);
}

/**
 * Ensure a directory exists, create it if it doesn't
 * 
 * @param string $dir_path Directory path (absolute or relative to app root)
 * @param int $permissions Directory permissions (default: 0755)
 * @return bool True if directory exists or was created successfully
 */
function ensure_directory_exists($dir_path, $permissions = 0755) {
    // Convert to absolute path if it's relative
    if (strpos($dir_path, '/') !== 0 && strpos($dir_path, ':\\') !== 1) {
        $dir_path = get_absolute_path($dir_path);
    }
    
    if (!file_exists($dir_path)) {
        return mkdir($dir_path, $permissions, true);
    }
    
    return is_dir($dir_path);
}

/**
 * Get the uploads directory path for a specific type
 * 
 * @param string $type Type of upload (e.g., 'datasets', 'profiles', 'temp')
 * @return string Path to the uploads directory
 */
function get_uploads_dir($type = '') {
    $dir = UPLOADS_DIR;
    
    if (!empty($type)) {
        $dir .= '/' . trim($type, '/\\');
    }
    
    ensure_directory_exists($dir);
    return $dir;
}

/**
 * Sanitize a filename to make it safe for filesystem
 * 
 * @param string $filename Original filename
 * @return string Sanitized filename
 */
function sanitize_filename($filename) {
    // Replace spaces with underscores
    $filename = str_replace(' ', '_', $filename);
    
    // Remove any character that is not alphanumeric, underscore, dash, or dot
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
    
    // Ensure filename is not too long (max 255 chars)
    if (strlen($filename) > 255) {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $filename = substr($basename, 0, 250 - strlen($ext)) . '.' . $ext;
    }
    
    return $filename;
}

/**
 * Generate a unique filename to avoid overwriting existing files
 * 
 * @param string $dir Directory where file will be stored
 * @param string $filename Original filename
 * @return string Unique filename
 */
function generate_unique_filename($dir, $filename) {
    $filename = sanitize_filename($filename);
    $file_path = $dir . '/' . $filename;
    
    if (!file_exists($file_path)) {
        return $filename;
    }
    
    // If file exists, add a unique identifier
    $info = pathinfo($filename);
    $ext = isset($info['extension']) ? '.' . $info['extension'] : '';
    $name = isset($info['filename']) ? $info['filename'] : $filename;
    
    $i = 1;
    while (file_exists($dir . '/' . $name . '_' . $i . $ext)) {
        $i++;
    }
    
    return $name . '_' . $i . $ext;
}

/**
 * Validate file type based on extension
 * 
 * @param string $filename Filename to check
 * @param array $allowed_extensions Array of allowed file extensions
 * @return bool True if file type is allowed
 */
function validate_file_type($filename, $allowed_extensions) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, array_map('strtolower', $allowed_extensions));
}

/**
 * Get file extension
 * 
 * @param string $filename Filename to check
 * @return string File extension (lowercase)
 */
function get_file_extension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Get web-accessible URL for a file
 * 
 * @param string $file_path File path relative to app root
 * @return string URL path to the file
 */
function get_file_url($file_path) {
    // Remove any leading slash to ensure proper URL formatting
    $file_path = ltrim($file_path, '/\\');
    
    // Get the base URL from server variables
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    
    // Get the directory name of the script being executed relative to document root
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    $base_url = $protocol . $host . $script_dir;
    
    // Remove '/includes' if the script is in that directory
    $base_url = preg_replace('|/includes$|', '', $base_url);
    
    // Ensure there's a trailing slash
    if (substr($base_url, -1) !== '/') {
        $base_url .= '/';
    }
    
    return $base_url . $file_path;
} 