@extends('emails.layout')

@section('subject', 'Sample Notification - ' . config('app.name'))

@section('content')
    <h2 style="color: #111827; font-size: 20px; margin-top: 0;">Hello {{ $recipient_name ?? 'User' }},</h2>

    <p>This is a sample notification email demonstrating the WASL email template.</p>

    <div class="alert-info">
        <strong>📋 Information:</strong> This template includes WASL branding and institutional credits.
    </div>

    <p>You can customize the content, add tables, alerts, and buttons as needed.</p>

    <a href="{{ $action_url ?? config('app.url') }}" class="email-button">
        View Details
    </a>

    <div class="divider"></div>

    <p style="font-size: 13px; color: #6b7280;">
        If you have any questions, please contact our support team at {{ config('app.contact.support_email') }}
    </p>

    <p style="font-size: 13px; color: #6b7280; margin-bottom: 0;">
        Best regards,<br>
        <strong>{{ config('app.name') }} Team</strong>
    </p>
@endsection
