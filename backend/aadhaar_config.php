<?php
// backend/aadhaar_config.php - Configuration for Aadhaar authentication
// Secret key for HMAC - MUST be set in environment variables in production
// This should be a long, random string and kept secret
define('AADHAAR_HMAC_KEY', getenv('AADHAAR_HMAC_KEY') ?: 'CHANGE_THIS_IN_PRODUCTION_TO_A_SECURE_RANDOM_KEY');

// OTP expiration time in seconds (5 minutes)
define('OTP_EXPIRY_TIME', 300);

// Aadhaar number validation regex
define('AADHAAR_REGEX', '/^\d{12}$/');

// Function to validate Aadhaar number format
function validate_aadhaar_number($aadhaar_number) {
    return preg_match(AADHAAR_REGEX, trim($aadhaar_number));
}

// Function to generate HMAC for Aadhaar number
function hmac_aadhaar($aadhaar_number) {
    return hash_hmac('sha256', trim($aadhaar_number), AADHAAR_HMAC_KEY);
}

// Function to generate OTP
function generate_otp() {
    return rand(100000, 999999);
}
?>
