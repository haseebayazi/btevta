<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .content h2 {
            color: #333;
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .content p {
            margin-bottom: 15px;
            color: #555;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .reset-button {
            display: inline-block;
            padding: 14px 36px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s;
        }
        .reset-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box p {
            margin: 0;
            color: #555;
            font-size: 14px;
        }
        .security-notice {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .security-notice p {
            margin: 0;
            color: #856404;
            font-size: 14px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer p {
            margin: 5px 0;
            color: #6c757d;
            font-size: 13px;
        }
        .divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 25px 0;
        }
        .alternative-link {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            word-break: break-all;
        }
        .alternative-link p {
            margin: 5px 0;
            font-size: 13px;
            color: #6c757d;
        }
        .alternative-link code {
            background-color: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>🔐 Password Reset Request</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <h2>Hello{{ isset($user) ? ' ' . $user->name : '' }}!</h2>

            <p>
                You are receiving this email because we received a password reset request for your account
                in the TheLeap Candidate Management System.
            </p>

            <!-- Reset Button -->
            <div class="button-container">
                <a href="{{ $resetUrl }}" class="reset-button">Reset Password</a>
            </div>

            <!-- Token Expiry Info -->
            <div class="info-box">
                <p>
                    <strong>⏰ Important:</strong> This password reset link will expire in
                    <strong>{{ config('auth.passwords.users.expire', 60) }} minutes</strong> for security reasons.
                </p>
            </div>

            <!-- Security Notice -->
            <div class="security-notice">
                <p>
                    <strong>⚠️ Security Notice:</strong> If you did not request a password reset,
                    no further action is required. Your account remains secure.
                </p>
            </div>

            <div class="divider"></div>

            <!-- Password Requirements -->
            <h2>Password Requirements</h2>
            <p>When creating your new password, please ensure it meets these requirements:</p>
            <ul style="color: #555; padding-left: 20px;">
                <li>At least 8 characters long</li>
                <li>Contains at least one lowercase letter (a-z)</li>
                <li>Contains at least one uppercase letter (A-Z)</li>
                <li>Contains at least one number (0-9)</li>
                <li>Contains at least one special character (!@#$%^&*)</li>
            </ul>

            <!-- Alternative Link -->
            <div class="alternative-link">
                <p><strong>Button not working?</strong> Copy and paste this URL into your browser:</p>
                <code>{{ $resetUrl }}</code>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>TheLeap Candidate Management System</strong></p>
            <p>Board of Technical Education and Vocational Training Authority</p>
            <p style="margin-top: 15px;">
                This is an automated email. Please do not reply to this message.
            </p>
            <p>
                If you have any questions, please contact your system administrator.
            </p>
        </div>
    </div>
</body>
</html>
