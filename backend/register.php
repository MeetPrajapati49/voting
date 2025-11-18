<?php
// backend/register.php - Enhanced version with detailed logging
require_once "config.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user, but log them
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Function to log debug info
function debug_log($message) {
    error_log("REGISTER DEBUG: " . $message);
}

// Function to send JSON response and exit
function send_response($success, $message, $data = []) {
    $response = array_merge(['success' => $success, 'message' => $message], $data);
    debug_log("Sending response: " . json_encode($response));
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    debug_log("OPTIONS request received - sending CORS headers");
    exit(0);
}

debug_log("Registration request started");
debug_log("Request method: " . $_SERVER['REQUEST_METHOD']);
debug_log("Content type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));

session_start();
debug_log("Session started successfully");

try {
    // Get raw input
    $raw_input = file_get_contents("php://input");
    debug_log("Raw input length: " . strlen($raw_input));
    debug_log("Raw input content: " . $raw_input);
    
    if (empty($raw_input)) {
        debug_log("Empty input received");
        send_response(false, "No data received");
    }

    // Parse JSON
    $input = json_decode($raw_input, true);
    $json_error = json_last_error();
    
    if ($json_error !== JSON_ERROR_NONE) {
        debug_log("JSON decode error: " . json_last_error_msg());
        send_response(false, "Invalid JSON data: " . json_last_error_msg());
    }
    
    debug_log("JSON decoded successfully: " . print_r($input, true));
    
    // Extract and validate input
    $username = trim($input["username"] ?? "");
    $password = trim($input["password"] ?? "");
    
    debug_log("Username: '$username' (length: " . strlen($username) . ")");
    debug_log("Password length: " . strlen($password));

    // Validation
    if (empty($username) || empty($password)) {
        debug_log("Missing username or password");
        send_response(false, "Username and password required");
    }

    if (strlen($username) < 3) {
        debug_log("Username too short");
        send_response(false, "Username must be at least 3 characters long");
    }

    if (strlen($password) < 6) {
        debug_log("Password too short");
        send_response(false, "Password must be at least 6 characters long");
    }

    // Check database connection
    if (!isset($conn) || !$conn) {
        debug_log("Database connection failed - conn variable not set");
        send_response(false, "Database connection error");
    }
    
    debug_log("Database connection verified");

    // Check if username exists
    debug_log("Checking if username exists");
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    if (!$stmt) {
        debug_log("Failed to prepare select statement: " . implode(", ", $conn->errorInfo()));
        send_response(false, "Database preparation error");
    }
    
    $stmt->execute([$username]);
    $existing_user = $stmt->fetch();
    
    if ($existing_user) {
        debug_log("Username already exists: " . $username);
        send_response(false, "Username already exists");
    }
    
    debug_log("Username is available");

    // Hash password
    debug_log("Hashing password");
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    if (!$hashed) {
        debug_log("Password hashing failed");
        send_response(false, "Password hashing error");
    }
    
    debug_log("Password hashed successfully");

    // Insert new user
    debug_log("Inserting new user");
    $stmt = $conn->prepare("INSERT INTO users (username, password, role, created_at) VALUES (?, ?, 'user', datetime('now'))");
    if (!$stmt) {
        debug_log("Failed to prepare insert statement: " . implode(", ", $conn->errorInfo()));
        send_response(false, "Database preparation error");
    }

    $success = $stmt->execute([$username, $hashed]);
    if (!$success) {
        $error_info = $stmt->errorInfo();
        debug_log("Failed to insert user: " . implode(", ", $error_info));
        send_response(false, "User creation failed: " . $error_info[2]);
    }

    // Get the new user ID
    $userId = $conn->lastInsertId();
    if (!$userId) {
        debug_log("Failed to get new user ID");
        send_response(false, "User ID retrieval failed");
    }
    
    debug_log("User created successfully with ID: " . $userId);

    // Set up session for auto-login
    $_SESSION["user"] = [
        "id" => $userId,
        "username" => $username,
        "role" => "user"
    ];
    
    debug_log("Session set up for user");

    // Send success response
    send_response(true, "Registration successful", [
        "username" => $username,
        "id" => (int)$userId,
        "role" => "user"
    ]);

} catch (PDOException $e) {
    debug_log("PDO Exception: " . $e->getMessage());
    debug_log("PDO Error Code: " . $e->getCode());
    send_response(false, "Database error occurred");
    
} catch (Exception $e) {
    debug_log("General Exception: " . $e->getMessage());
    debug_log("Stack trace: " . $e->getTraceAsString());
    send_response(false, "Server error occurred");
}

debug_log("Registration script ended unexpectedly");
send_response(false, "Unexpected script termination");
?>