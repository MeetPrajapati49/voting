<?php
// Simple test to verify email system functionality
echo "=== SIMPLE EMAIL SYSTEM TEST ===\n\n";

try {
    // Test 1: Include required files
    echo "1. Testing file includes...\n";
    require_once "config.php";
    require_once "email_functions.php";
    echo "✅ Required files included successfully\n";

    // Test 2: Check PHPMailer
    echo "\n2. Testing PHPMailer...\n";
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    echo "✅ PHPMailer is accessible\n";

    // Test 3: Check SMTP config
    echo "\n3. Checking SMTP configuration...\n";
    echo "SMTP_HOST: " . SMTP_HOST . "\n";
    echo "SMTP_PORT: " . SMTP_PORT . "\n";
    echo "SMTP_USERNAME: " . (SMTP_USERNAME !== 'your-gmail@gmail.com' ? '***configured***' : 'NOT SET') . "\n";
    echo "FROM_EMAIL: " . FROM_EMAIL . "\n";

    if (SMTP_USERNAME === 'your-gmail@gmail.com') {
        echo "⚠️  SMTP_USERNAME still set to default - needs configuration\n";
    } else {
        echo "✅ SMTP configuration looks good\n";
    }

    // Test 4: Test OTP generation
    echo "\n4. Testing OTP generation...\n";
    $otp = generate_secure_otp();
    echo "Generated OTP: $otp\n";
    if (strlen($otp) === 6 && ctype_digit($otp)) {
        echo "✅ OTP generation working correctly\n";
    } else {
        echo "❌ OTP generation failed\n";
    }

    // Test 5: Test Gmail validation
    echo "\n5. Testing Gmail validation...\n";
    $test_emails = [
        'test@gmail.com' => true,
        'test@yahoo.com' => false,
        'invalid-email' => false
    ];

    foreach ($test_emails as $email => $expected) {
        $result = is_valid_gmail($email);
        $status = ($result === $expected) ? '✅' : '❌';
        echo "  $email: Expected " . ($expected ? 'valid' : 'invalid') . ", Got " . ($result ? 'valid' : 'invalid') . " $status\n";
    }

    // Test 6: Database connection
    echo "\n6. Testing database connection...\n";
    try {
        $conn = new PDO("mysql:host=localhost;dbname=voting;charset=utf8mb4", 'root', '');
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✅ Database connection successful\n";
    } catch (PDOException $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    }

    echo "\n=== TEST SUMMARY ===\n";
    echo "✅ Basic functionality test completed!\n";
    echo "\nNext steps:\n";
    echo "1. Update SMTP credentials in config.php\n";
    echo "2. Access http://localhost/Voting/backend/test_email_system.php for full test\n";
    echo "3. Test actual email sending with real Gmail credentials\n";

} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
?>
