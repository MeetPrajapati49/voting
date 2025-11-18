<?php
// backend/login.php
require_once "config.php";
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // TODO: restrict this in production
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $input = json_decode(file_get_contents("php://input"), true);
    $username = trim($input["username"] ?? "");
    $password = trim($input["password"] ?? "");

    if (!$username || !$password) {
        echo json_encode(["success" => false, "message" => "Username and password required"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user"] = [
            "id" => $user["id"],
            "username" => $user["username"],
            "role" => $user["role"]
        ];
        
        // Return user data including ID
        echo json_encode([
            "success" => true, 
            "username" => $user["username"],
            "id" => $user["id"],
            "role" => $user["role"]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid username or password"]);
    }
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Server error occurred"]);
}
?>