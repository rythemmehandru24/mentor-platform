<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mentor_platform');

// Application configuration
// FIX: Ensure SITE_URL matches your actual folder name 'mentor-platform-main'
define('SITE_URL', 'http://localhost/mentor-platform-main/'); 
define('UPLOAD_DIR', 'uploads/');

// FIX: Use __DIR__ for a dynamic path that works regardless of your folder name
define('UPLOAD_PATH', str_replace('\\', '/', __DIR__ . '/' . UPLOAD_DIR));
define('UPLOAD_URL', SITE_URL . UPLOAD_DIR);

define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
// FIX: Set to 0 for Localhost. Secure cookies require HTTPS, which will block 
// sessions on a standard XAMPP setup.
ini_set('session.cookie_secure', 0); 

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // FIX: Set default fetch mode to Associative to prevent errors in your other files
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper functions
// FIX: Added null coalescing to sanitize_input to prevent PHP 8.1+ Deprecated errors
function sanitize_input($data) {
    $data = $data ?? ''; 
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
    return true;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>