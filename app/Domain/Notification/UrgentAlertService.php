<?php

namespace App\Domain\Notification;

class UrgentAlertService
{
    public function __construct(
        private NotificationChannel $notificationChannel
    ) {}

    public function alert(string $to, string $message): void
    {
        $this->notificationChannel->send($to, "URGENT: {$message}");
    }
}
