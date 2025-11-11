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
        .password-box {
            background-color: #fff;
            border: 2px solid #3498db;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            letter-spacing: 2px;
        }
        .info-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
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
        .button {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        ul {
            padding-left: 20px;
        }
        li {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>BTEVTA - Password Reset</h1>
        <p>Bureau of Overseas Employment</p>
    </div>

    <div class="content">
        <h2>Hello {{ $user->name }},</h2>

        <p>Your password has been reset by <strong>{{ $resetBy->name }}</strong> ({{ $resetBy->role }}).</p>

        <p>Your new temporary password is:</p>

        <div class="password-box">
            {{ $newPassword }}
        </div>

        <div class="info-box">
            <strong>⚠️ Important Security Information:</strong>
            <ul>
                <li>This is a temporary password. Please change it immediately after logging in.</li>
                <li>Do not share this password with anyone.</li>
                <li>The password is case-sensitive.</li>
                <li>For your security, this email will not be sent again.</li>
            </ul>
        </div>

        <p><strong>How to change your password:</strong></p>
        <ol>
            <li>Log in to the BTEVTA system using the temporary password above</li>
            <li>Go to your Profile/Settings page</li>
            <li>Click on "Change Password"</li>
            <li>Enter your temporary password and choose a new secure password</li>
        </ol>

        <p style="text-align: center;">
            <a href="{{ config('app.url') }}/login" class="button">Login to BTEVTA System</a>
        </p>

        <p><strong>Your Account Details:</strong></p>
        <ul>
            <li><strong>Email:</strong> {{ $user->email }}</li>
            <li><strong>Role:</strong> {{ ucfirst(str_replace('_', ' ', $user->role)) }}</li>
            @if($user->campus)
            <li><strong>Campus:</strong> {{ $user->campus->name }}</li>
            @endif
            <li><strong>Reset Date:</strong> {{ now()->format('F d, Y h:i A') }}</li>
        </ul>

        <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">

        <p style="font-size: 14px; color: #666;">
            If you did not request this password reset or have concerns about your account security,
            please contact the system administrator immediately at
            <a href="mailto:admin@btevta.gov.pk">admin@btevta.gov.pk</a>
        </p>
    </div>

    <div class="footer">
        <p>
            <strong>Bureau of Technical Education and Vocational Training Authority (BTEVTA)</strong><br>
            Overseas Employment Management System<br>
            Punjab, Pakistan
        </p>
        <p style="margin-top: 15px;">
            This is an automated email. Please do not reply to this message.<br>
            © {{ date('Y') }} BTEVTA. All rights reserved.
        </p>
    </div>
</body>
</html>
