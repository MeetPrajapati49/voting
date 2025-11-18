<?php
// backend/update_smtp_password.php - Script to update SMTP password in config.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['smtp_password'] ?? '');

    if (empty($new_password)) {
        echo "<div style='color: red;'>❌ Please enter a password</div>";
    } elseif (strlen($new_password) !== 16) {
        echo "<div style='color: red;'>❌ Gmail App Password should be exactly 16 characters</div>";
    } else {
        $config_file = __DIR__ . '/config.php';
        $config_content = file_get_contents($config_file);

        // Find the SMTP_PASSWORD line and replace it
        $pattern = "/define\('SMTP_PASSWORD',\s*'[^']*'\);/";
        $replacement = "define('SMTP_PASSWORD', '$new_password');";

        if (preg_match($pattern, $config_content)) {
            $config_content = preg_replace($pattern, $replacement, $config_content);

            if (file_put_contents($config_file, $config_content)) {
                echo "<div style='color: green;'>✅ SMTP password updated successfully!</div>";
                echo "<p><a href='test_email_system.php'>Click here to test the email system</a></p>";
            } else {
                echo "<div style='color: red;'>❌ Failed to update config.php. Check file permissions.</div>";
            }
        } else {
            echo "<div style='color: red;'>❌ Could not find SMTP_PASSWORD definition in config.php</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update SMTP Password</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; max-width: 600px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type='password'] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .info { background-color: #e7f3ff; border: 1px solid #b3d7ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Update Gmail SMTP App Password</h1>

    <div class="info">
        <h3>📧 Generate New Gmail App Password</h3>
        <ol>
            <li>Go to <a href="https://myaccount.google.com/apppasswords" target="_blank">Google App Passwords</a></li>
            <li>Select "Mail" and "Other (custom name)"</li>
            <li>Enter "Voting System" as the name</li>
            <li>Copy the 16-character password generated</li>
            <li>Paste it below and click Update</li>
        </ol>
    </div>

    <form method="POST">
        <div class="form-group">
            <label for="smtp_password">New Gmail App Password (16 characters):</label>
            <input type="password" id="smtp_password" name="smtp_password" placeholder="abcd-efgh-ijkl-mnop" maxlength="16" required>
        </div>

        <button type="submit">Update SMTP Password</button>
    </form>

    <hr>
    <p><a href="test_email_system.php">Test Current Email Configuration</a></p>
    <p><a href="setup_email.php">Back to Email Setup</a></p>
</body>
</html>
