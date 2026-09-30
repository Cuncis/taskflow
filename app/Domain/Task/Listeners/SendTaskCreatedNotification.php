<?php

namespace App\Domain\Task\Listeners;

use App\Domain\Notification\NotificationChannel;
use App\Domain\Task\Events\TaskCreated;

class SendTaskCreatedNotification
{
    /**
     * Create the event listener.
     */
    public function __construct(private NotificationChannel $notificationChannel) {}

    /**
     * Handle the event.
     */
    public function handle(TaskCreated $event): void
    {
        $this->notificationChannel->send(
            to: 'team@taskflow.test',
            message: "New task created: {$event->task->title}",
        );
    }
}
