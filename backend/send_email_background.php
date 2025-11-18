<?php
// backend/send_email_background.php - Background email sender
require_once "config.php";
require_once "email_functions.php";

// Get parameters from command line or POST
if ($argc > 1) {
    // CLI mode
    $email = $argv[1];
    $voteDataJson = $argv[2];
} else {
    // POST mode (fallback)
    $email = $_POST['email'] ?? '';
    $voteDataJson = $_POST['vote_data'] ?? '';
}

if (!$email || !$voteDataJson) {
    error_log("send_email_background: Missing email or vote_data");
    exit(1);
}

$voteData = json_decode($voteDataJson, true);
if (!$voteData) {
    error_log("send_email_background: Invalid vote_data JSON");
    exit(1);
}

error_log("Background: Attempting to send vote confirmation email to: " . $email);
$emailSent = send_vote_confirmation_email($email, $voteData);

if (!$emailSent) {
    error_log("Background: Failed to send vote confirmation email to: " . $email);
    exit(1);
} else {
    error_log("Background: Vote confirmation email sent successfully to: " . $email);
    exit(0);
}
?>
