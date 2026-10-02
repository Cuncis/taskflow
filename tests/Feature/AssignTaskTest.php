<?php

namespace Tests\Feature;

use App\Domain\Notification\EmailChannel;
use App\Domain\Notification\NotificationChannel;
use App\Domain\Notification\NotificationPreference;
use App\Domain\Task\Events\TaskAssigned;
use App\Domain\Task\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class AssignTaskTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Group('happy-path')]
    public function test_it_assigns_a_task_to_a_user(): void
    {
        Event::fake([TaskAssigned::class]);

        $task = Task::factory()->unassigned()->create();
        $assignee = User::factory()->create();

        $this->actingAs($this->user)->patchJson("/tasks/{$task->id}/assign", ['assignee_id' => $assignee->id])
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Task assigned successfully',
                'data' => ['id' => $task->id, 'assignee_id' => $assignee->id, 'assignee' => ['id' => $assignee->id, 'name' => $assignee->name]],
            ]);

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'assignee_id' => $assignee->id]);
    }

    #[Group('happy-path')]
    public function test_it_fires_a_task_assigned_event_with_the_task_and_assignee(): void
    {
        // Fake only this event; a bare Event::fake() would swallow every framework event too.
        Event::fake([TaskAssigned::class]);

        $task = Task::factory()->unassigned()->create();
        $assignee = User::factory()->create();

        $this->actingAs($this->user)->patchJson("/tasks/{$task->id}/assign", ['assignee_id' => $assignee->id])
            ->assertStatus(200);

        Event::assertDispatched(
            TaskAssigned::class,
            fn (TaskAssigned $event) => $event->task->is($task) && $event->assignee->is($assignee),
        );
        Event::assertDispatchedTimes(TaskAssigned::class, 1);
    }

    #[Group('validation-failure')]
    public function test_it_requires_an_assignee_id(): void
    {
        Event::fake([TaskAssigned::class]);

        $task = Task::factory()->unassigned()->create();

        $this->actingAs($this->user)->patchJson("/tasks/{$task->id}/assign", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('assignee_id');

        Event::assertNotDispatched(TaskAssigned::class);
    }

    #[Group('validation-failure')]
    public function test_it_rejects_an_assignee_who_does_not_exist(): void
    {
        Event::fake([TaskAssigned::class]);

        $task = Task::factory()->unassigned()->create();

        $this->actingAs($this->user)->patchJson("/tasks/{$task->id}/assign", ['assignee_id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('assignee_id');

        $this->assertNull($task->fresh()->assignee_id);
        Event::assertNotDispatched(TaskAssigned::class);
    }

    #[Group('validation-failure')]
    public function test_it_returns_404_for_a_task_that_does_not_exist(): void
    {
        Event::fake([TaskAssigned::class]);

        $this->actingAs($this->user)->patchJson('/tasks/999999/assign', ['assignee_id' => $this->user->id])
            ->assertStatus(404);

        Event::assertNotDispatched(TaskAssigned::class);
    }

    #[Group('auth')]
    public function test_it_rejects_an_unauthenticated_user(): void
    {
        Event::fake([TaskAssigned::class]);

        $task = Task::factory()->unassigned()->create();
        $assignee = User::factory()->create();

        $this->patchJson("/tasks/{$task->id}/assign", ['assignee_id' => $assignee->id])
            ->assertStatus(401);

        $this->assertNull($task->fresh()->assignee_id);
        Event::assertNotDispatched(TaskAssigned::class);
    }

    #[Group('happy-path')]
    public function test_it_assigns_a_task_through_the_full_http_flow_and_notifies_the_assignee(): void
    {
        $task = Task::factory()->unassigned()->create();
        $assignee = User::factory()->create(['notification_preference' => NotificationPreference::Email]);

        $fakeChannel = new class implements NotificationChannel
        {
            /** @var array<int, array{to: string, message: string}> */
            public array $sent = [];

            public function send(string $to, string $message): void
            {
                $this->sent[] = compact('to', 'message');
            }
        };

        // The factory resolves the CONCRETE channel for the assignee's preference (not the
        // NotificationChannel interface), so that is the binding to swap for this test.
        $this->app->instance(EmailChannel::class, $fakeChannel);

        $this->actingAs($this->user)->patchJson("/tasks/{$task->id}/assign", ['assignee_id' => $assignee->id])
            ->assertStatus(200)
            ->assertJsonPath('data.assignee.id', $assignee->id);

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'assignee_id' => $assignee->id]);
        $this->assertCount(1, $fakeChannel->sent);
        $this->assertSame($assignee->email, $fakeChannel->sent[0]['to']);
        $this->assertStringContainsString($task->title, $fakeChannel->sent[0]['message']);
    }

    #[Group('happy-path')]
    public function test_it_allows_reassigning_a_task_that_already_has_an_assignee(): void
    {
        Event::fake([TaskAssigned::class]);

        $original = User::factory()->create();
        $task = Task::factory()->create(['assignee_id' => $original->id]);
        $new = User::factory()->create();

        $this->actingAs($this->user)->patchJson("/tasks/{$task->id}/assign", ['assignee_id' => $new->id])
            ->assertStatus(200)
            ->assertJsonPath('data.assignee.id', $new->id);

        $this->assertSame($new->id, $task->fresh()->assignee_id);
    }

    #[Group('happy-path')]
    public function test_assigning_to_the_current_assignee_succeeds_without_notifying_again(): void
    {
        $assignee = User::factory()->create(['notification_preference' => NotificationPreference::Email]);
        $task = Task::factory()->create(['assignee_id' => $assignee->id]);

        $fakeChannel = new class implements NotificationChannel
        {
            public int $sends = 0;

            public function send(string $to, string $message): void
            {
                $this->sends++;
            }
        };
        $this->app->instance(EmailChannel::class, $fakeChannel); // real event + listener run; only the channel is swapped

        $this->actingAs($this->user)->patchJson("/tasks/{$task->id}/assign", ['assignee_id' => $assignee->id])
            ->assertStatus(200)
            ->assertJsonPath('data.assignee.id', $assignee->id);

        $this->assertSame($assignee->id, $task->fresh()->assignee_id);
        $this->assertSame(0, $fakeChannel->sends);
    }
}
