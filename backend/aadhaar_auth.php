<?php
// backend/aadhaar_auth.php

// Prevent HTML error output - ensure all responses are JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once "config.php";
require_once "aadhaar_config.php";
require_once "email_functions.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function validate_input($input) {
    $errors = [];
    
    $aadhaar_number = preg_replace('/\s+/', '', trim($input["aadhaar_number"] ?? ""));
    $mobile_number = preg_replace('/\s+/', '', trim($input["mobile_number"] ?? ""));
    $email = trim($input["email"] ?? "");
    
    // Validate Aadhaar
    if (empty($aadhaar_number)) {
        $errors[] = "Aadhaar number is required";
    } elseif (!preg_match('/^\d{12}$/', $aadhaar_number)) {
        $errors[] = "Aadhaar number must be exactly 12 digits";
    }
    
    // Validate contact methods
    if (empty($mobile_number) && empty($email)) {
        $errors[] = "Either mobile number or email is required";
    }
    
    if (!empty($mobile_number) && !preg_match('/^\d{10}$/', $mobile_number)) {
        $errors[] = "Mobile number must be exactly 10 digits";
    }
    
    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        } elseif (!preg_match('/@gmail\.com$/i', $email)) {
            $errors[] = "Only Gmail addresses are allowed";
        }
    }
    
    return [
        'aadhaar_number' => $aadhaar_number,
        'mobile_number' => $mobile_number,
        'email' => $email,
        'errors' => $errors
    ];
}

try {
    $json_input = file_get_contents("php://input");
    $input = json_decode($json_input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(["success" => false, "message" => "Invalid JSON input"]);
        exit;
    }

    $action = $input["action"] ?? "";
    $otp = trim($input["otp"] ?? "");

    if (!$action) {
        echo json_encode(["success" => false, "message" => "Action required"]);
        exit;
    }

    if ($action === "initiate") {
        // Validate all inputs
        $validated = validate_input($input);
        
        if (!empty($validated['errors'])) {
            echo json_encode(["success" => false, "message" => implode(", ", $validated['errors'])]);
            exit;
        }
        
        $aadhaar_number = $validated['aadhaar_number'];
        $mobile_number = $validated['mobile_number'];
        $email = $validated['email'];

        // Rate limiting: allow max 5 OTP requests per session per 10 minutes
        if (!isset($_SESSION['otp_requests'])) {
            $_SESSION['otp_requests'] = [];
        }
        $current_time = time();
        // Remove requests older than 10 minutes
        $_SESSION['otp_requests'] = array_filter($_SESSION['otp_requests'], function($timestamp) use ($current_time) {
            return ($current_time - $timestamp) < 600;
        });
        if (count($_SESSION['otp_requests']) >= 5) {
            echo json_encode(["success" => false, "message" => "Too many OTP requests. Please try again later."]);
            exit;
        }
        $_SESSION['otp_requests'][] = $current_time;

        $aadhaar_hmac = hmac_aadhaar($aadhaar_number);
        $otp_code = generate_otp();

        // Check if user exists
        $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE aadhaar_hmac = ?");
        $stmt->execute([$aadhaar_hmac]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Store in session
        $_SESSION['aadhaar_auth'] = [
            'aadhaar_hmac' => $aadhaar_hmac,
            'mobile_number' => $mobile_number,
            'email' => $email,
            'otp' => $otp_code,
            'expires' => time() + 300, // 5 minutes
            'user_id' => $user['id'] ?? null,
            'attempts' => 0 // Track OTP attempts
        ];

        $response = [
            "success" => true,
            "user_exists" => $user ? true : false
        ];

        // Send OTP via preferred method
        if (!empty($email)) {
            error_log("Sending OTP via email to: $email");
            $email_sent = send_otp_email($email, $otp_code);

            if ($email_sent) {
                $response["message"] = "OTP sent to your Gmail address";
            } else {
                $response["success"] = false;
                $response["message"] = "Failed to send OTP email. Please try again.";
            }

        } elseif (!empty($mobile_number)) {
            // TODO: Implement SMS service
            $response["success"] = false;
            $response["message"] = "SMS OTP functionality not yet implemented";
        }

        echo json_encode($response);

    } elseif ($action === "verify") {
        if (empty($otp)) {
            echo json_encode(["success" => false, "message" => "OTP is required"]);
            exit;
        }

        $validated = validate_input($input);
        $aadhaar_number = $validated['aadhaar_number'];
        $aadhaar_hmac = hmac_aadhaar($aadhaar_number);

        // Check session
        if (!isset($_SESSION['aadhaar_auth'])) {
            echo json_encode(["success" => false, "message" => "Session expired. Please restart verification."]);
            exit;
        }

        $auth_data = $_SESSION['aadhaar_auth'];
        
        // Validate session data
        if ($auth_data['aadhaar_hmac'] !== $aadhaar_hmac) {
            echo json_encode(["success" => false, "message" => "Aadhaar number mismatch"]);
            exit;
        }

        if ($auth_data['expires'] < time()) {
            unset($_SESSION['aadhaar_auth']);
            echo json_encode(["success" => false, "message" => "OTP expired. Please request a new one."]);
            exit;
        }

        // Track attempts
        $_SESSION['aadhaar_auth']['attempts']++;

        if ($_SESSION['aadhaar_auth']['attempts'] > 3) {
            unset($_SESSION['aadhaar_auth']);
            echo json_encode(["success" => false, "message" => "Too many failed attempts. Please restart verification."]);
            exit;
        }

        if ($auth_data['otp'] != $otp) {
            $remaining_attempts = 3 - $_SESSION['aadhaar_auth']['attempts'];
            echo json_encode([
                "success" => false, 
                "message" => "Invalid OTP. {$remaining_attempts} attempts remaining."
            ]);
            exit;
        }

        // OTP verified successfully
        $user_id = $auth_data['user_id'];

        // Double-check if user exists with this Aadhaar HMAC
        $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE aadhaar_hmac = ?");
        $stmt->execute([$aadhaar_hmac]);
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_user) {
            // Existing user login (whether from session or found by Aadhaar)
            $_SESSION["user"] = [
                "id" => $existing_user["id"],
                "username" => $existing_user["username"],
                "role" => $existing_user["role"],
                "aadhaar_verified" => true
            ];

            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            $message = "Login successful";
        } else {
            // New user registration - generate unique username
            $base_username = "user_" . substr($aadhaar_number, -4);
            $username = $base_username;
            $counter = 1;

            // Ensure username is unique
            while (true) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetchColumn() == 0) {
                    break; // Username is available
                }
                $username = $base_username . "_" . $counter;
                $counter++;
            }

            $email_to_insert = $auth_data['email'] ?? null;
            $mobile_to_insert = $auth_data['mobile_number'] ?? null;

            $stmt = $conn->prepare("INSERT INTO users (username, aadhaar_hmac, email, mobile, role, created_at) VALUES (?, ?, ?, ?, 'voter', NOW())");
            $stmt->execute([$username, $aadhaar_hmac, $email_to_insert, $mobile_to_insert]);
            $user_id = $conn->lastInsertId();

            $_SESSION["user"] = [
                "id" => $user_id,
                "username" => $username,
                "role" => "voter",
                "aadhaar_verified" => true
            ];

            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            $message = "Registration and login successful";
        }

        // Clear OTP session
        unset($_SESSION['aadhaar_auth']);
        
        echo json_encode([
            "success" => true, 
            "message" => $message,
            "user" => $_SESSION["user"]
        ]);

    } else {
        echo json_encode(["success" => false, "message" => "Invalid action"]);
    }

} catch (Exception $e) {
    error_log("Aadhaar auth error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Server error: " . $e->getMessage()]);
}
?>