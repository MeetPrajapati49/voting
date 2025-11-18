<?php
// backend/email_functions.php - Email functions using PHPMailer
require_once "config.php";
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * Send OTP email using PHPMailer with SMTP
 * @param string $to_email Recipient's Gmail address
 * @param string $otp_code 6-digit OTP code
 * @return bool True if email sent successfully, false otherwise
 */
function send_otp_email($to_email, $otp_code) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF; // Set to DEBUG_SERVER for debugging
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Voting System OTP Code';
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                .otp-code { font-size: 24px; font-weight: bold; color: #4CAF50; text-align: center; padding: 20px; background-color: #f9f9f9; border-radius: 5px; margin: 20px 0; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Voting System OTP Verification</h2>
                </div>
                <p>Dear User,</p>
                <p>You have requested an OTP to access the voting system. Please use the following OTP code to complete your verification:</p>
                <div class='otp-code'>{$otp_code}</div>
                <p><strong>Important:</strong></p>
                <ul>
                    <li>This OTP is valid for 5 minutes only</li>
                    <li>Do not share this OTP with anyone</li>
                    <li>If you didn't request this OTP, please ignore this email</li>
                </ul>
                <p>If you're having trouble with the OTP, please contact support.</p>
                <div class='footer'>
                    <p>This is an automated message from the Voting System. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        $mail->AltBody = "Your Voting System OTP Code\n\n" .
                        "OTP Code: {$otp_code}\n\n" .
                        "This OTP is valid for 5 minutes only.\n" .
                        "Do not share this OTP with anyone.\n" .
                        "If you didn't request this OTP, please ignore this email.";

        $mail->send();
        error_log("OTP email sent successfully to: $to_email");
        return true;

    } catch (Exception $e) {
        error_log("Failed to send OTP email to $to_email: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send voting confirmation email using PHPMailer with SMTP
 * @param string $to_email Recipient's Gmail address
 * @param array $vote_data Array containing vote information
 * @return bool True if email sent successfully, false otherwise
 */
function send_vote_confirmation_email($to_email, $vote_data) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Voting Confirmation - Your Vote Has Been Recorded';
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2196F3; color: white; padding: 20px; text-align: center; }
                .vote-details { background-color: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .vote-detail { margin: 10px 0; }
                .label { font-weight: bold; color: #555; }
                .value { color: #333; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; }
                .success-icon { font-size: 48px; color: #4CAF50; text-align: center; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>✅ Vote Recorded Successfully</h2>
                </div>

                <div class='success-icon'>✓</div>

                <p>Dear Voter,</p>
                <p>Your vote has been successfully recorded in our system. Here are the details of your vote:</p>

                <div class='vote-details'>
                    <div class='vote-detail'>
                        <span class='label'>Election:</span>
                        <span class='value'>{$vote_data['election_name']}</span>
                    </div>
                    <div class='vote-detail'>
                        <span class='label'>Candidate:</span>
                        <span class='value'>{$vote_data['candidate_name']}</span>
                    </div>
                    <div class='vote-detail'>
                        <span class='label'>Voting Period:</span>
                        <span class='value'>{$vote_data['election_period']}</span>
                    </div>
                    <div class='vote-detail'>
                        <span class='label'>Date & Time:</span>
                        <span class='value'>{$vote_data['vote_datetime']}</span>
                    </div>
                    <div class='vote-detail'>
                        <span class='label'>Vote ID:</span>
                        <span class='value'>{$vote_data['vote_id']}</span>
                    </div>
                </div>

                <p><strong>Important Notes:</strong></p>
                <ul>
                    <li>Your vote is confidential and secure</li>
                    <li>You cannot vote again in this election</li>
                    <li>Keep this confirmation email for your records</li>
                    <li>If you have any concerns about your vote, please contact election officials</li>
                </ul>

                <p>Thank you for participating in the democratic process!</p>

                <div class='footer'>
                    <p>This is an automated confirmation from the Voting System. Please do not reply to this email.</p>
                    <p>For technical support, please contact the system administrator.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        $mail->AltBody = "Vote Recorded Successfully\n\n" .
                        "Election: {$vote_data['election_name']}\n" .
                        "Candidate: {$vote_data['candidate_name']}\n" .
                        "Voting Period: {$vote_data['election_period']}\n" .
                        "Date & Time: {$vote_data['vote_datetime']}\n" .
                        "Vote ID: {$vote_data['vote_id']}\n\n" .
                        "Your vote has been successfully recorded.\n" .
                        "Thank you for participating in the democratic process!";

        $mail->send();
        error_log("Vote confirmation email sent successfully to: $to_email");
        return true;

    } catch (Exception $e) {
        error_log("Failed to send vote confirmation email to $to_email: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Validate if email is a Gmail address
 * @param string $email Email address to validate
 * @return bool True if valid Gmail address, false otherwise
 */
function is_valid_gmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) &&
           preg_match('/@gmail\.com$/i', $email);
}
?>
