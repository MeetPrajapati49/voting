<?php
// backend/test_email_system.php - Test script for email functionality
require_once "config.php";
require_once "email_functions.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Email System Test Suite</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
    .error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
    .info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
    .test-result { padding: 10px; margin: 10px 0; border-radius: 3px; }
</style>";

/**
 * Test 1: Check PHPMailer Installation
 */
echo "<div class='test-section info'>";
echo "<h3>Test 1: PHPMailer Installation Check</h3>";

try {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    echo "<div class='test-result success'>✅ PHPMailer is installed and accessible</div>";
} catch (Exception $e) {
    echo "<div class='test-result error'>❌ PHPMailer not accessible: " . $e->getMessage() . "</div>";
}
echo "</div>";

/**
 * Test 2: SMTP Configuration Check
 */
echo "<div class='test-section info'>";
echo "<h3>Test 2: SMTP Configuration Check</h3>";

$smtp_config = [
    'SMTP_HOST' => SMTP_HOST,
    'SMTP_PORT' => SMTP_PORT,
    'SMTP_USERNAME' => SMTP_USERNAME,
    'SMTP_PASSWORD' => SMTP_PASSWORD ? '***configured***' : 'NOT SET',
    'SMTP_ENCRYPTION' => SMTP_ENCRYPTION,
    'FROM_EMAIL' => FROM_EMAIL,
    'FROM_NAME' => FROM_NAME
];

echo "<div class='test-result info'>Current SMTP Configuration:</div>";
echo "<ul>";
foreach ($smtp_config as $key => $value) {
    echo "<li><strong>$key:</strong> $value</li>";
}
echo "</ul>";

$warnings = [];
if (SMTP_USERNAME === 'your-gmail@gmail.com') {
    $warnings[] = "SMTP_USERNAME is still set to default value";
}
if (SMTP_PASSWORD === 'your-app-password') {
    $warnings[] = "SMTP_PASSWORD is still set to default value";
}
if (FROM_EMAIL === 'your-gmail@gmail.com') {
    $warnings[] = "FROM_EMAIL is still set to default value";
}

if (!empty($warnings)) {
    echo "<div class='test-result error'>⚠️ Configuration Issues:</div>";
    echo "<ul>";
    foreach ($warnings as $warning) {
        echo "<li>$warning</li>";
    }
    echo "</ul>";
    echo "<p><em>Update these values in config.php with your actual Gmail credentials</em></p>";
} else {
    echo "<div class='test-result success'>✅ SMTP configuration looks good</div>";
}
echo "</div>";

/**
 * Test 3: Test OTP Email Function
 */
echo "<div class='test-section info'>";
echo "<h3>Test 3: OTP Email Function Test</h3>";

$test_email = "test@example.com";
$test_otp = "123456";

echo "<p>Testing send_otp_email() function with:</p>";
echo "<ul><li>Email: $test_email</li><li>OTP: $test_otp</li></ul>";

$start_time = microtime(true);
$result = send_otp_email($test_email, $test_otp);
$end_time = microtime(true);

$execution_time = round(($end_time - $start_time) * 1000, 2); // in milliseconds

if ($result) {
    echo "<div class='test-result success'>✅ OTP email function executed successfully in {$execution_time}ms</div>";
} else {
    echo "<div class='test-result error'>❌ OTP email function failed after {$execution_time}ms</div>";
    echo "<p>Check error logs for detailed information</p>";
}
echo "</div>";

/**
 * Test 4: Test Vote Confirmation Email Function
 */
echo "<div class='test-section info'>";
echo "<h3>Test 4: Vote Confirmation Email Function Test</h3>";

$test_vote_data = [
    'election_name' => 'Test Election 2024',
    'candidate_name' => 'Test Candidate',
    'election_period' => 'January 1-15, 2024',
    'vote_datetime' => date('Y-m-d H:i:s'),
    'vote_id' => 'TEST123'
];

echo "<p>Testing send_vote_confirmation_email() function with sample data:</p>";
echo "<ul>";
foreach ($test_vote_data as $key => $value) {
    echo "<li><strong>$key:</strong> $value</li>";
}
echo "</ul>";

$start_time = microtime(true);
$result = send_vote_confirmation_email($test_email, $test_vote_data);
$end_time = microtime(true);

$execution_time = round(($end_time - $start_time) * 1000, 2);

if ($result) {
    echo "<div class='test-result success'>✅ Vote confirmation email function executed successfully in {$execution_time}ms</div>";
} else {
    echo "<div class='test-result error'>❌ Vote confirmation email function failed after {$execution_time}ms</div>";
    echo "<p>Check error logs for detailed information</p>";
}
echo "</div>";

/**
 * Test 5: Session Management Test
 */
echo "<div class='test-section info'>";
echo "<h3>Test 5: Session Management Test</h3>";

echo "<p>Testing session configuration and OTP expiry settings:</p>";
echo "<ul>";
echo "<li>OTP_EXPIRY_TIME: " . OTP_EXPIRY_TIME . " seconds (" . (OTP_EXPIRY_TIME / 60) . " minutes)</li>";
echo "<li>OTP_LENGTH: " . OTP_LENGTH . " digits</li>";
echo "<li>Session cookie samesite: " . ini_get('session.cookie_samesite') . "</li>";
echo "<li>Session cookie secure: " . ini_get('session.cookie_secure') . "</li>";
echo "<li>Session cookie httponly: " . ini_get('session.cookie_httponly') . "</li>";
echo "</ul>";

if (OTP_EXPIRY_TIME === 300 && OTP_LENGTH === 6) {
    echo "<div class='test-result success'>✅ Session configuration matches requirements (5 minutes expiry, 6-digit OTP)</div>";
} else {
    echo "<div class='test-result error'>❌ Session configuration doesn't match requirements</div>";
}
echo "</div>";

/**
 * Test 6: Database Connection Test
 */
echo "<div class='test-section info'>";
echo "<h3>Test 6: Database Connection Test</h3>";

try {
    // Test database connection
    $test_conn = new PDO("mysql:host=localhost;dbname=voting;charset=utf8mb4", 'root', '');
    $test_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if required tables exist
    $tables = ['users', 'polls', 'participants', 'votes'];
    $missing_tables = [];

    foreach ($tables as $table) {
        $stmt = $test_conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if (!$stmt->fetch()) {
            $missing_tables[] = $table;
        }
    }

    if (empty($missing_tables)) {
        echo "<div class='test-result success'>✅ Database connection successful and all required tables exist</div>";
    } else {
        echo "<div class='test-result error'>❌ Missing tables: " . implode(', ', $missing_tables) . "</div>";
        echo "<p>Run init_db.php to create the required tables</p>";
    }

} catch (PDOException $e) {
    echo "<div class='test-result error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
}
echo "</div>";

/**
 * Test 7: Gmail Validation Test
 */
echo "<div class='test-section info'>";
echo "<h3>Test 7: Gmail Validation Test</h3>";

$test_emails = [
    'valid@gmail.com' => true,
    'invalid@gmail.co' => false,
    'test@yahoo.com' => false,
    'user.name+tag@gmail.com' => true,
    'notanemail' => false
];

echo "<p>Testing Gmail validation function:</p>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Email</th><th>Expected</th><th>Result</th><th>Status</th></tr>";

foreach ($test_emails as $email => $expected) {
    $result = is_valid_gmail($email);
    $status = ($result === $expected) ? '✅' : '❌';
    $result_text = $result ? 'Valid' : 'Invalid';
    $expected_text = $expected ? 'Valid' : 'Invalid';

    $row_class = ($result === $expected) ? 'success' : 'error';
    echo "<tr class='$row_class'>";
    echo "<td>$email</td>";
    echo "<td>$expected_text</td>";
    echo "<td>$result_text</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

echo "<div class='test-section info'>";
echo "<h3>Test Summary</h3>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Update SMTP credentials in config.php with your actual Gmail App Password</li>";
echo "<li>Run this test again to verify email functionality</li>";
echo "<li>Test the actual OTP and voting confirmation flows through the web interface</li>";
echo "<li>Monitor error logs for any issues during real usage</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?>
