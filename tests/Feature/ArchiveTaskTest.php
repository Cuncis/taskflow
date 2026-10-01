<?php

namespace Tests\Feature;

use App\Domain\Task\Events\TaskArchived;
use App\Domain\Task\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class ArchiveTaskTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Group('happy-path')]
    public function test_it_archives_a_done_task(): void
    {
        Event::fake([TaskArchived::class]);

        $task = Task::factory()->completed()->create();

        $this->actingAs($this->user)->deleteJson("/tasks/{$task->id}/archive")
            ->assertStatus(200)
            ->assertJson(['message' => 'Task archived successfully', 'data' => ['id' => $task->id]])
            ->assertJsonPath('data.archived_at', fn ($value) => $value !== null);

        $this->assertNotNull($task->fresh()->archived_at);
        Event::assertDispatched(TaskArchived::class, fn (TaskArchived $e) => $e->task->is($task));
        Event::assertDispatchedTimes(TaskArchived::class, 1);
    }

    #[Group('validation-failure')]
    public function test_it_returns_422_instead_of_a_500_for_a_task_that_is_not_done(): void
    {
        Event::fake([TaskArchived::class]);

        $task = Task::factory()->create(['status' => 'todo']);

        $this->actingAs($this->user)->deleteJson("/tasks/{$task->id}/archive")
            ->assertStatus(422)
            ->assertJson(['message' => 'Only completed tasks can be archived.']);

        $this->assertNull($task->fresh()->archived_at);
        Event::assertNotDispatched(TaskArchived::class);
    }

    #[Group('validation-failure')]
    public function test_it_returns_404_for_a_task_that_does_not_exist(): void
    {
        $this->actingAs($this->user)->deleteJson('/tasks/999999/archive')
            ->assertStatus(404);
    }

    #[Group('auth')]
    public function test_it_rejects_an_unauthenticated_user(): void
    {
        Event::fake([TaskArchived::class]);

        $task = Task::factory()->completed()->create();

        $this->deleteJson("/tasks/{$task->id}/archive")
            ->assertStatus(401);

        $this->assertNull($task->fresh()->archived_at);
        Event::assertNotDispatched(TaskArchived::class);
    }
}
