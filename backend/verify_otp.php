<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$otp = trim($_POST['otp'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

if (!preg_match('/^\d{6}$/', $otp)) {
    echo json_encode(['success' => false, 'message' => 'Invalid OTP format']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT otp, expiry FROM email_otps WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'OTP not found. Please request a new one.']);
        exit;
    }

    if ($row['otp'] !== $otp) {
        echo json_encode(['success' => false, 'message' => 'Incorrect OTP']);
        exit;
    }

    if (strtotime($row['expiry']) < time()) {
        echo json_encode(['success' => false, 'message' => 'OTP expired. Please request a new one.']);
        exit;
    }

    // OTP is valid, delete it to prevent reuse
    $stmt = $conn->prepare("DELETE FROM email_otps WHERE email = ?");
    $stmt->execute([$email]);

    // Fetch user by email to set login session
    $userStmt = $conn->prepare("SELECT id, username, role, email FROM users WHERE email = ?");
    $userStmt->execute([$email]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found for this email']);
        exit;
    }

    // Set login session
    session_start();
    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
        'email' => $user['email']
    ];
    session_write_close(); // Close session to allow concurrent requests

    echo json_encode(['success' => true, 'message' => 'OTP verified successfully']);
} catch (PDOException $e) {
    error_log("OTP verify error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>
