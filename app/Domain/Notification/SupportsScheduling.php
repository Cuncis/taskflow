<?php

namespace App\Domain\Notification;

interface SupportsScheduling
{
    public function scheduleForLater(string $to, string $message, \DateTimeInterface $sendAt): void;
}
