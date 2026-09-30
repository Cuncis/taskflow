<?php

namespace App\Domain\Notification;

enum NotificationPreference: string
{
    case Email = 'email';
    case Sms = 'sms';
    case Slack = 'slack';
    case Push = 'push';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Sms => 'Sms',
            self::Slack => 'Slack',
            self::Push => 'Push',
        };
    }
}
