<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('subject', config('app.full_name'))</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .email-header {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            padding: 30px 20px;
            text-align: center;
            color: #ffffff;
        }
        .email-logo {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .email-title {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 5px 0;
        }
        .email-tagline {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }
        .email-body {
            padding: 30px 20px;
            color: #374151;
            line-height: 1.6;
        }
        .email-button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .email-footer {
            background-color: #f9fafb;
            padding: 25px 20px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer-credits {
            font-size: 11px;
            color: #6b7280;
            line-height: 1.6;
            margin: 10px 0;
        }
        .footer-contact {
            font-size: 11px;
            color: #9ca3af;
            margin: 10px 0;
        }
        .footer-copyright {
            font-size: 10px;
            color: #9ca3af;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }
        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        .alert-info {
            background-color: #dbeafe;
            border-left: 4px solid #2563eb;
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .alert-warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="email-logo">🌐</div>
            <h1 class="email-title">{{ config('app.full_name') }}</h1>
            <p class="email-tagline">{{ config('app.tagline') }}</p>
        </div>

        <!-- Body -->
        <div class="email-body">
            @yield('content')
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <div style="font-size: 14px; color: #374151; font-weight: 600; margin-bottom: 10px;">
                🌐 {{ config('app.full_name') }}
            </div>

            <div class="footer-credits">
                <strong>Product Conceived by:</strong> {{ config('app.product_credits.conceived_by') }}
                &nbsp;•&nbsp;
                <strong>Developed by:</strong> {{ config('app.product_credits.developed_by') }}
            </div>

            <div class="footer-credits">
                <strong>Operated by:</strong> {{ config('app.operated_by') }}
            </div>

            <div class="footer-contact">
                📧 {{ config('app.contact.support_email') }}
                &nbsp;•&nbsp;
                📞 {{ config('app.contact.support_phone') }}
                &nbsp;•&nbsp;
                🌐 {{ config('app.contact.website') }}
            </div>

            <div class="footer-copyright">
                &copy; {{ date('Y') }} All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
