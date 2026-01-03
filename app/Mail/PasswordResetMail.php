<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Password;

/**
 * AUDIT FIX: Rewritten to use password reset tokens instead of sending plaintext passwords.
 * Sending passwords via email is an OWASP violation (A07:2021 - Identification and Authentication Failures).
 *
 * Previous implementation sent the actual password in the email which could be:
 * - Intercepted in transit
 * - Stored in email logs
 * - Visible in recipient's inbox indefinitely
 *
 * New implementation sends a secure, time-limited reset link instead.
 */
class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public User $resetBy;
    public string $resetToken;
    public string $resetUrl;

    /**
     * Create a new message instance.
     *
     * @param User $user The user whose password is being reset
     * @param User $resetBy The admin who initiated the reset
     * @param string $resetToken The password reset token
     */
    public function __construct(User $user, User $resetBy, string $resetToken)
    {
        $this->user = $user;
        $this->resetBy = $resetBy;
        $this->resetToken = $resetToken;
        $this->resetUrl = url(route('password.reset', [
            'token' => $resetToken,
            'email' => $user->email,
        ], false));
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Reset Request - BTEVTA System',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
