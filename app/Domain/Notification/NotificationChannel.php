<?php

namespace App\Domain\Notification;

interface NotificationChannel
{
    public function send(string $to, string $message): void;
}
