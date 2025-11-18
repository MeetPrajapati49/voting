<?php
// test_cli.php - Script to test Aadhaar OTP authentication and vote submission with session persistence

function send_post_request($url, $data, &$cookieFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "Curl error: " . curl_error($ch) . "\n";
    }
    curl_close($ch);
    return $response;
}

$baseUrl = "http://localhost:8000/backend";
$cookieFile = tempnam(sys_get_temp_dir(), 'cookie');

echo "=== Initiate OTP ===\n";
$initData = [
    "action" => "initiate",
    "aadhaar_number" => "123456789012",
    "email" => "test@gmail.com"
];
$initResponse = send_post_request("$baseUrl/aadhaar_auth.php", $initData, $cookieFile);
echo "Response: $initResponse\n";

$initResult = json_decode($initResponse, true);
if (!$initResult || empty($initResult['otp'])) {
    echo "Failed to get OTP from initiate response.\n";
    exit(1);
}

$otp = $initResult['otp'];
echo "Using OTP: $otp\n";

echo "=== Verify OTP ===\n";
$verifyData = [
    "action" => "verify",
    "aadhaar_number" => "123456789012",
    "otp" => $otp
];
$verifyResponse = send_post_request("$baseUrl/aadhaar_auth.php", $verifyData, $cookieFile);
echo "Response: $verifyResponse\n";

$verifyResult = json_decode($verifyResponse, true);
if (!$verifyResult || empty($verifyResult['success']) || !$verifyResult['success']) {
    echo "OTP verification failed.\n";
    exit(1);
}

$userId = $verifyResult['user']['id'] ?? null;
if (!$userId) {
    echo "User ID not found after verification.\n";
    exit(1);
}

echo "=== Submit Vote ===\n";
$voteData = [
    "user_id" => $userId,
    "participant_id" => 1,
    "poll_id" => 1
];
$voteResponse = send_post_request("$baseUrl/submit_vote.php", $voteData, $cookieFile);
echo "Response: $voteResponse\n";

unlink($cookieFile);
?>
