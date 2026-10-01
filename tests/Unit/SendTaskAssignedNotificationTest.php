<?php

namespace Tests\Unit;

use App\Domain\Notification\UserNotifier;
use App\Domain\Task\Events\TaskAssigned;
use App\Domain\Task\Listeners\SendTaskAssignedNotification;
use App\Domain\Task\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class SendTaskAssignedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_notifies_the_assignee_through_user_notifier(): void
    {
        $task = Task::factory()->create(['title' => 'Ship it']);
        $user = User::factory()->create();

        $notifier = $this->mock(UserNotifier::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('notify')->once()
                ->with(\Mockery::on(fn (User $u) => $u->is($user)), "You've been assigned: Ship it");
        });

        (new SendTaskAssignedNotification($notifier))->handle(new TaskAssigned($task, $user));
    }
}
