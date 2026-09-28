<?php

namespace App\Domain\Notification;

interface SupportsAttachments
{
    public function sendWithAttachment(string $to, string $message, string $attachmentPath): void;
}
