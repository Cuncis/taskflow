<?php

namespace App\Domain\Notification;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackChannel implements NotificationChannel
{
    /**
     * A Slack outage must not break the caller (e.g. task creation), but it must not be
     * silent either: a non-2xx response is logged as a warning and send() returns normally.
     */
    public function send(string $to, string $message): void
    {
        $response = Http::post('https://hooks.slack.example.com/services/fake-webhook-url', [
            'text' => $message,
            'channel' => $to,
        ]);

        if ($response->failed()) {
            Log::warning('Slack notification failed', [
                'channel' => $to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }
}
