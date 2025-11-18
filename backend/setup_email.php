<?php
// backend/setup_email.php - Setup script for Gmail SMTP configuration
echo "<h1>Gmail SMTP Setup for Voting System</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; max-width: 800px; }
    .form-group { margin: 15px 0; }
    label { display: block; margin-bottom: 5px; font-weight: bold; }
    input[type='email'], input[type='password'] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .info { background-color: #e7f3ff; border: 1px solid #b3d7ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
    .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
    .success { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
    button { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
    button:hover { background-color: #0056b3; }
</style>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gmail = trim($_POST['gmail'] ?? '');
    $app_password = trim($_POST['app_password'] ?? '');

    if (empty($gmail) || empty($app_password)) {
        echo "<div class='warning'>❌ Please fill in all fields</div>";
    } elseif (!filter_var($gmail, FILTER_VALIDATE_EMAIL) || !preg_match('/@gmail\.com$/i', $gmail)) {
        echo "<div class='warning'>❌ Please enter a valid Gmail address</div>";
    } elseif (strlen($app_password) !== 16) {
        echo "<div class='warning'>❌ Gmail App Password should be 16 characters long</div>";
    } else {
        // Update config.php
        $config_file = __DIR__ . '/config.php';
        $config_content = file_get_contents($config_file);

        // Replace placeholders
        $config_content = str_replace("'your-gmail@gmail.com'", "'$gmail'", $config_content);
        $config_content = str_replace("'your-app-password'", "'$app_password'", $config_content);

        if (file_put_contents($config_file, $config_content)) {
            echo "<div class='success'>✅ SMTP configuration updated successfully!</div>";
            echo "<p><strong>Updated values:</strong></p>";
            echo "<ul>";
            echo "<li>SMTP_USERNAME: $gmail</li>";
            echo "<li>SMTP_PASSWORD: *** (16 characters)</li>";
            echo "<li>FROM_EMAIL: $gmail</li>";
            echo "</ul>";
            echo "<p><a href='test_email_system.php'>Click here to test the email system</a></p>";
        } else {
            echo "<div class='warning'>❌ Failed to update config.php. Please check file permissions.</div>";
        }
    }
}
?>

<div class="info">
    <h3>📧 Gmail SMTP Setup Instructions</h3>
    <ol>
        <li><strong>Enable 2-Factor Authentication</strong> on your Gmail account</li>
        <li>Go to <a href="https://myaccount.google.com/security" target="_blank">Google Account Security</a></li>
        <li>Click on "2-Step Verification" and enable it</li>
        <li>Go to "App passwords" section</li>
        <li>Select "Mail" and "Other (custom name)"</li>
        <li>Enter "Voting System" as the name</li>
        <li>Copy the 16-character password generated</li>
        <li>Fill in the form below with your Gmail address and the app password</li>
    </ol>
</div>

<div class="warning">
    <h3>⚠️ Security Notice</h3>
    <ul>
        <li>Never share your Gmail password or app password</li>
        <li>This setup is for development/testing only</li>
        <li>In production, use environment variables or secure credential storage</li>
        <li>The app password will be stored in plain text in config.php</li>
    </ul>
</div>

<form method="POST">
    <div class="form-group">
        <label for="gmail">Gmail Address:</label>
        <input type="email" id="gmail" name="gmail" placeholder="your-gmail@gmail.com" required>
    </div>

    <div class="form-group">
        <label for="app_password">Gmail App Password (16 characters):</label>
        <input type="password" id="app_password" name="app_password" placeholder="abcd-efgh-ijkl-mnop" maxlength="16" required>
        <small>This is NOT your regular Gmail password</small>
    </div>

    <button type="submit">Update SMTP Configuration</button>
</form>

<hr>
<p><a href="test_email_system.php">Test Current Email Configuration</a></p>
<p><a href="EMAIL_TESTING_README.md">View Detailed Setup Guide</a></p>
