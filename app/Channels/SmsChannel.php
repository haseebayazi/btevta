<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    /**
     * Send the given notification via SMS.
     * Supports Twilio out of the box; extend for other providers.
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);
        $phone   = $notifiable->routeNotificationFor('sms', $notification);

        if (!$phone || !$message) {
            return;
        }

        $provider = config('services.sms.provider', 'twilio');

        match ($provider) {
            'twilio'  => $this->sendViaTwilio($phone, $message),
            'log'     => $this->sendViaLog($phone, $message),
            default   => $this->sendViaLog($phone, $message),
        };
    }

    private function sendViaTwilio(string $phone, string $message): void
    {
        $sid   = config('services.sms.twilio.sid');
        $token = config('services.sms.twilio.token');
        $from  = config('services.sms.from');

        if (!$sid || !$token || !$from) {
            Log::warning('SmsChannel: Twilio credentials not configured; falling back to log driver.');
            $this->sendViaLog($phone, $message);
            return;
        }

        // Dynamically resolve Twilio client to avoid hard dependency when not installed
        if (!class_exists(\Twilio\Rest\Client::class)) {
            Log::warning('SmsChannel: twilio/sdk package not installed. SMS not sent.');
            return;
        }

        $client = new \Twilio\Rest\Client($sid, $token);
        $client->messages->create($phone, ['from' => $from, 'body' => $message]);
    }

    private function sendViaLog(string $phone, string $message): void
    {
        Log::channel('daily')->info("SMS to {$phone}: {$message}");
    }
}
