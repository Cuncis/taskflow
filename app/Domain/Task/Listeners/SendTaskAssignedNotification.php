<?php

namespace App\Domain\Task\Listeners;

use App\Domain\Notification\UserNotifier;
use App\Domain\Task\Events\TaskAssigned;

class SendTaskAssignedNotification
{
    public function __construct(
        private UserNotifier $userNotifier,
    ) {}

    public function handle(TaskAssigned $event): void
    {
        $this->userNotifier->notify(
            $event->assignee,
            "You've been assigned: {$event->task->title}",
        );
    }
}
