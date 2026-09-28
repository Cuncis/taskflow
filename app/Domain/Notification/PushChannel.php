<?php

namespace App\Domain\Notification;

class PushChannel implements NotificationChannel
{
    public function send(string $to, string $message): void
    {
        // Pretend this calls a real push service (FCM, APNs) — stubbed for now.
        logger("Push to {$to}: {$message}");
    }
}
