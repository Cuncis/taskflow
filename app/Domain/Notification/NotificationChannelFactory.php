<?php

namespace App\Domain\Notification;

use Illuminate\Contracts\Container\Container;

class NotificationChannelFactory
{
    public function __construct(
        private Container $container
    ) {}

    public function make(NotificationPreference $preference): NotificationChannel
    {
        return match ($preference) {
            NotificationPreference::Email => $this->container->make(EmailChannel::class),
            NotificationPreference::Sms => $this->container->make(SmsChannel::class),
            NotificationPreference::Slack => $this->container->make(SlackChannel::class),
            NotificationPreference::Push => $this->container->make(PushChannel::class),
        };
    }
}
