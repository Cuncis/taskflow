<?php

namespace Tests\Feature;

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

        $this->actingAs($this->user)->postJson("/tasks/{$task->id}/assign", ['assignee_id' => $assignee->id])
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Task assigned successfully',
                'data' => ['id' => $task->id, 'assignee_id' => $assignee->id],
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

        $this->actingAs($this->user)->postJson("/tasks/{$task->id}/assign", ['assignee_id' => $assignee->id])
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

        $this->actingAs($this->user)->postJson("/tasks/{$task->id}/assign", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('assignee_id');

        Event::assertNotDispatched(TaskAssigned::class);
    }

    #[Group('validation-failure')]
    public function test_it_rejects_an_assignee_who_does_not_exist(): void
    {
        Event::fake([TaskAssigned::class]);

        $task = Task::factory()->unassigned()->create();

        $this->actingAs($this->user)->postJson("/tasks/{$task->id}/assign", ['assignee_id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('assignee_id');

        $this->assertNull($task->fresh()->assignee_id);
        Event::assertNotDispatched(TaskAssigned::class);
    }

    #[Group('validation-failure')]
    public function test_it_returns_404_for_a_task_that_does_not_exist(): void
    {
        Event::fake([TaskAssigned::class]);

        $this->actingAs($this->user)->postJson('/tasks/999999/assign', ['assignee_id' => $this->user->id])
            ->assertStatus(404);

        Event::assertNotDispatched(TaskAssigned::class);
    }

    #[Group('auth')]
    public function test_it_rejects_an_unauthenticated_user(): void
    {
        Event::fake([TaskAssigned::class]);

        $task = Task::factory()->unassigned()->create();
        $assignee = User::factory()->create();

        $this->postJson("/tasks/{$task->id}/assign", ['assignee_id' => $assignee->id])
            ->assertStatus(401);

        $this->assertNull($task->fresh()->assignee_id);
        Event::assertNotDispatched(TaskAssigned::class);
    }
}
