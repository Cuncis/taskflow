<?php

namespace App\Domain\Notification;

class SlackChannel implements NotificationChannel
{
    public function send(string $to, string $message): void
    {
        // Pretend this posts to a real Slack webhook — stubbed for now.
        logger("Slack to {$to}: {$message}");
    }
}
