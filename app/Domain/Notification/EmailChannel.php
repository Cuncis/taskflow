<?php

namespace App\Domain\Notification;

class EmailChannel implements NotificationChannel, SupportsAttachments, SupportsScheduling
{
    public function send(string $to, string $message): void
    {
        logger("Email to {$to}: {$message}");
    }

    public function sendWithAttachment(string $to, string $message, string $attachmentPath): void
    {
        logger("Email to {$to} with attachment {$attachmentPath}: {$message}");
    }

    public function scheduleForLater(string $to, string $message, \DateTimeInterface $sendAt): void
    {
        logger("Email to {$to} scheduled for {$sendAt->format('Y-m-d H:i:s')}: {$message}");
    }
}
