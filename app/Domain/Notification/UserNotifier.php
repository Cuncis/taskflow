<?php

namespace App\Domain\Notification;

use App\Models\User;

class UserNotifier
{
    public function __construct(
        private NotificationChannelFactory $channelFactory
    ) {}

    public function notify(User $user, string $message): void
    {
        $channel = $this->channelFactory->make($user->notification_preference);
        $channel->send(
            to: $user->email,
            message: $message
        );
    }
}
