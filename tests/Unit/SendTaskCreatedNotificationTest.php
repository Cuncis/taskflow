<?php

namespace Tests\Unit;

use App\Domain\Notification\NotificationChannel;
use App\Domain\Task\Events\TaskCreated;
use App\Domain\Task\Listeners\SendTaskCreatedNotification;
use App\Domain\Task\Models\Task;
use Mockery;
use Tests\TestCase;

class SendTaskCreatedNotificationTest extends TestCase
{
    public function test_it_sends_a_notification_with_the_task_title_in_the_message(): void
    {
        $mockChannel = Mockery::mock(NotificationChannel::class);

        // Verified at teardown: exactly one send(), to this address, message containing the title.
        $mockChannel->shouldReceive('send')
            ->once()
            ->with('team@taskflow.test', Mockery::pattern('/Fix the login bug/'));

        $listener = new SendTaskCreatedNotification($mockChannel);
        $listener->handle(new TaskCreated(new Task(['title' => 'Fix the login bug'])));
    }
}
