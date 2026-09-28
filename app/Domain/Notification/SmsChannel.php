<?php

namespace App\Domain\Notification;

class SmsChannel implements NotificationChannel
{
    public function send(string $to, string $message): void
    {
        // Pretend this calls a real SMS API (Twilio, etc.) — stubbed for now.
        logger("SMS to {$to}: {$message}");
    }
}
