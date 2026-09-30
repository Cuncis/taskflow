<?php

namespace App\Domain\Notification;

use Illuminate\Support\Facades\Http;

class SlackChannel implements NotificationChannel
{
    public function send(string $to, string $message): void
    {
        Http::post('https://hooks.slack.example.com/services/fake-webhook-url', [
            'text' => $message,
            'channel' => $to,
        ]);
    }
}
