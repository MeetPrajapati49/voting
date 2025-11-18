<?php
// Session configuration for CORS support - only set if session not already started
if (session_status() == PHP_SESSION_NONE) {
    // Set session save path for local development
    if (!ini_get('session.save_path')) {
        ini_set('session.save_path', sys_get_temp_dir());
    }
    ini_set('session.cookie_samesite', 'Lax');
    // For local development, set secure to false if not using HTTPS
    ini_set('session.cookie_secure', false);
    ini_set('session.cookie_httponly', true);
    ini_set('session.use_only_cookies', 1);
    // Commented out session.cookie_domain to fix session persistence issues
    // ini_set('session.cookie_domain', 'localhost');
    ini_set('session.cookie_path', '/');
    session_name('voting_session');
    session_start();
}

// MySQL database configuration
$host = 'localhost';
$dbname = 'voting';
$user = 'root';
$pass = '';

// Gmail SMTP Configuration for PHPMailer - Use environment variables in production
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 465);
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '4meetp@gmail.com');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: 'bajm akif aaqt fjky');
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'ssl');
define('FROM_EMAIL', getenv('FROM_EMAIL') ?: '4meetp@gmail.com');
define('FROM_NAME', getenv('FROM_NAME') ?: 'Voting System');

// DSN for MySQL
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

try {
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Safety check: log if elections table is empty (possible reset)
    $stmt = $conn->query("SELECT COUNT(*) FROM polls");
    $pollCount = $stmt->fetchColumn();
    if ($pollCount == 0) {
        error_log("Warning: No polls found in database. Possible reset or truncation.");
    }
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $e->getMessage()]));
}

// Email OTP Configuration
define('OTP_EXPIRY_TIME', 300); // 5 minutes in seconds
define('OTP_LENGTH', 6); // 6-digit OTP

// Function to generate secure OTP
function generate_secure_otp() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}
