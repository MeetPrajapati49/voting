# Email System Testing Guide

This guide explains how to test the email OTP and voting confirmation functionality in the Voting System.

## Overview

The voting system now includes:
- **OTP Email Authentication**: Users can authenticate using Aadhaar number + Gmail address
- **Voting Confirmation Emails**: Users receive confirmation emails after successful voting
- **Session Management**: 5-minute OTP expiry with proper session handling

## Prerequisites

1. **XAMPP/WAMP Server** running with PHP 8.3+
2. **MySQL Database** configured
3. **Gmail Account** with 2FA enabled and App Password generated

## Quick Start

### 1. Configure SMTP Settings

Edit `backend/config.php` and update these values:

```php
define('SMTP_USERNAME', 'your-actual-gmail@gmail.com');
define('SMTP_PASSWORD', 'your-16-character-app-password');
define('FROM_EMAIL', 'your-actual-gmail@gmail.com');
```

**How to get Gmail App Password:**
1. Enable 2-Factor Authentication on your Gmail account
2. Go to Google Account settings > Security > App passwords
3. Generate a new app password for "Mail"
4. Use the 16-character password (not your regular Gmail password)

### 2. Run Test Scripts

Open these URLs in your browser:

- **Email System Test**: `http://localhost/Voting/backend/test_email_system.php`
- **Session Management Test**: `http://localhost/Voting/backend/test_session_management.php`

### 3. Test Real Functionality

1. **Test OTP Email**:
   - Go to your voting application
   - Enter Aadhaar number and Gmail address
   - Click "Send OTP"
   - Check your Gmail inbox for the OTP email

2. **Test Voting Confirmation**:
   - Complete Aadhaar authentication
   - Navigate to voting page
   - Submit a vote
   - Check your Gmail inbox for confirmation email

## Test Results

### Expected Results

**Email System Test should show:**
- ✅ PHPMailer is installed and accessible
- ✅ SMTP configuration looks good (after updating credentials)
- ✅ OTP email function executed successfully
- ✅ Vote confirmation email function executed successfully
- ✅ Session configuration matches requirements

**Session Management Test should show:**
- ✅ Session configuration is correct for CORS support
- ✅ OTP generation working correctly
- ✅ 5-minute expiry logic working correctly
- ✅ Session data structure supports all required fields

### Common Issues & Solutions

1. **"SMTP Authentication Failed"**
   - Verify Gmail App Password is correct
   - Ensure 2FA is enabled on Gmail account
   - Check if Gmail is blocking "less secure app" access

2. **"Connection Timeout"**
   - Check internet connection
   - Verify SMTP settings (smtp.gmail.com, port 587, TLS)
   - Try disabling antivirus/firewall temporarily

3. **"PHPMailer not accessible"**
   - Run `composer install` in the project root
   - Check if vendor/phpmailer directory exists

4. **"Database connection failed"**
   - Start MySQL service in XAMPP
   - Run `backend/init_db.php` to create tables

## Monitoring & Debugging

### Check Error Logs
- **XAMPP Error Log**: `C:\xampp\apache\logs\error.log`
- **PHP Error Log**: Check your PHP configuration for error_log path
- **Application Logs**: Check browser console and server error logs

### Debug Mode
In `backend/aadhaar_auth.php`, set:
```php
$isDev = true; // Set to false in production
```

## Security Notes

1. **Never commit real credentials** to version control
2. **Use environment variables** for production deployments
3. **Monitor email sending** to prevent abuse
4. **Implement rate limiting** for OTP requests
5. **Use HTTPS** in production for secure email transmission

## Production Deployment

For production use:

1. **Update configuration** for production SMTP server
2. **Set proper error handling** (`$isDev = false`)
3. **Implement proper logging** system
4. **Add rate limiting** for email sending
5. **Use environment variables** for sensitive data
6. **Set up monitoring** for email delivery

## Support

If you encounter issues:

1. Check the test scripts output for specific errors
2. Verify SMTP configuration
3. Check Gmail account settings
4. Review error logs
5. Test with a different Gmail account

---

**Last Updated**: $(date)
**Tested with**: PHP 8.3.3, PHPMailer 6.10, Gmail SMTP
