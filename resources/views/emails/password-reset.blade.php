<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-top: none;
        }
        .button {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 16px;
            font-weight: bold;
        }
        .button:hover {
            background-color: #2980b9;
        }
        .info-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .security-box {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            background-color: #ecf0f1;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
            border-radius: 0 0 5px 5px;
        }
        ul {
            padding-left: 20px;
        }
        li {
            margin: 10px 0;
        }
        .link-fallback {
            word-break: break-all;
            font-size: 12px;
            color: #666;
            background: #f5f5f5;
            padding: 10px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>TheLeap - Password Reset</h1>
        <p>Bureau of Overseas Employment</p>
    </div>

    <div class="content">
        <h2>Hello {{ $user->name }},</h2>

        <p>A password reset has been requested for your account by <strong>{{ $resetBy->name }}</strong> ({{ $resetBy->role }}).</p>

        <div class="security-box">
            <strong>Security Notice:</strong>
            <p>For your security, we no longer send passwords via email. Instead, please use the secure link below to set a new password of your choice.</p>
        </div>

        <p style="text-align: center;">
            <a href="{{ $resetUrl }}" class="button">Reset My Password</a>
        </p>

        <p class="link-fallback">
            <strong>If the button doesn't work, copy and paste this link into your browser:</strong><br>
            {{ $resetUrl }}
        </p>

        <div class="info-box">
            <strong>Important Information:</strong>
            <ul>
                <li>This link will expire in <strong>60 minutes</strong></li>
                <li>This link can only be used once</li>
                <li>After clicking the link, you will be prompted to create a new password</li>
                <li>Choose a strong password with at least 8 characters</li>
            </ul>
        </div>

        <p><strong>Your Account Details:</strong></p>
        <ul>
            <li><strong>Email:</strong> {{ $user->email }}</li>
            <li><strong>Role:</strong> {{ ucfirst(str_replace('_', ' ', $user->role)) }}</li>
            @if($user->campus)
            <li><strong>Campus:</strong> {{ $user->campus->name }}</li>
            @endif
            <li><strong>Reset Requested:</strong> {{ now()->format('F d, Y h:i A') }}</li>
        </ul>

        <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">

        <p style="font-size: 14px; color: #666;">
            <strong>Did you not request this reset?</strong><br>
            If you did not request this password reset, please contact the system administrator immediately at
            <a href="mailto:admin@theleap.org">admin@theleap.org</a>. Your account may be at risk.
        </p>
    </div>

    <div class="footer">
        <p>
            <strong>Bureau of Technical Education and Vocational Training Authority (TheLeap)</strong><br>
            Overseas Employment Management System<br>
            Punjab, Pakistan
        </p>
        <p style="margin-top: 15px;">
            This is an automated email. Please do not reply to this message.<br>
            &copy; {{ date('Y') }} TheLeap. All rights reserved.
        </p>
    </div>
</body>
</html>
