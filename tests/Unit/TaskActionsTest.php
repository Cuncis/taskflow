<?php

namespace Tests\Unit;

use App\Domain\Task\Actions\ArchiveTaskAction;
use App\Domain\Task\Actions\AssignTaskAction;
use App\Domain\Task\Actions\CompleteTaskAction;
use App\Domain\Task\Actions\CreateTaskAction;
use App\Domain\Task\Actions\MoveTaskAction;
use App\Domain\Task\Actions\UnarchiveTaskAction;
use App\Domain\Task\Events\TaskArchived;
use App\Domain\Task\Events\TaskAssigned;
use App\Domain\Task\Events\TaskCompleted;
use App\Domain\Task\Events\TaskCreated;
use App\Domain\Task\Events\TaskMoved;
use App\Domain\Task\Events\TaskUnarchived;
use App\Domain\Task\Models\Task;
use App\Domain\Task\Repositories\FakeTaskRepository;
use App\Domain\Task\TaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;
use TypeError;

class TaskActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_task_persists_and_announces_it(): void
    {
        Event::fake([TaskCreated::class]);

        $task = (new CreateTaskAction(new FakeTaskRepository))(['title' => 'Isolated test task', 'status' => 'todo', 'project_id' => 1]);

        $this->assertSame('Isolated test task', $task->title);
        Event::assertDispatched(TaskCreated::class);
    }

    public function test_complete_task_marks_done_and_announces_it(): void
    {
        // TaskCreated is faked too: setup must not run real listeners
        // (they'd queue a job that reloads the never-persisted in-memory task).
        Event::fake([TaskCreated::class, TaskCompleted::class]);

        $repository = new FakeTaskRepository;
        $task = (new CreateTaskAction($repository))(['title' => 'Finish me', 'status' => 'in_progress']);

        $completed = (new CompleteTaskAction($repository))($task);

        $this->assertSame(TaskStatus::Done, $completed->status);
        Event::assertDispatched(TaskCompleted::class, fn (TaskCompleted $event) => $event->task->is($completed));
    }

    public function test_assign_task_sets_assignee_and_fires_event(): void
    {
        Event::fake([TaskAssigned::class]);

        $task = Task::factory()->unassigned()->create();
        $user = User::factory()->create();

        (new AssignTaskAction)($task, $user);

        $this->assertSame($user->id, $task->fresh()->assignee_id);
        Event::assertDispatched(TaskAssigned::class, fn (TaskAssigned $e) => $e->assignee->is($user));
    }

    public function test_move_task_changes_status_and_fires_event(): void
    {
        Event::fake([TaskMoved::class]);

        $task = Task::factory()->create(['status' => 'todo']);

        (new MoveTaskAction)($task, TaskStatus::InProgress);

        $this->assertSame(TaskStatus::InProgress, $task->fresh()->status);
        Event::assertDispatched(TaskMoved::class, fn (TaskMoved $e) => $e->fromStatus === 'todo' && $e->toStatus === 'in_progress');
    }

    public function test_move_task_to_same_status_is_a_noop(): void
    {
        Event::fake([TaskMoved::class]);

        $task = Task::factory()->create(['status' => 'todo']);

        (new MoveTaskAction)($task, TaskStatus::Todo);

        Event::assertNotDispatched(TaskMoved::class);
    }

    public function test_an_unknown_status_cannot_be_turned_into_a_task_status(): void
    {
        // Invalid statuses are now rejected before MoveTaskAction is ever reached.
        $this->assertNull(TaskStatus::tryFrom('bogus'));
        $this->assertSame(TaskStatus::InProgress, TaskStatus::from('in_progress'));
    }

    public function test_move_task_only_accepts_a_task_status(): void
    {
        $task = Task::factory()->create(['status' => 'todo']);

        $this->expectException(TypeError::class);

        (new MoveTaskAction)($task, 'in_progress'); // deliberately the wrong type
    }

    public function test_archive_task_stamps_archived_at_and_fires_event(): void
    {
        Event::fake([TaskArchived::class]);

        $task = Task::factory()->completed()->create();

        (new ArchiveTaskAction)($task);

        $this->assertNotNull($task->fresh()->archived_at);
        Event::assertDispatched(TaskArchived::class);
    }

    public function test_archive_task_rejects_unfinished_tasks(): void
    {
        Event::fake([TaskArchived::class]);

        $task = Task::factory()->create(['status' => 'todo']);

        try {
            (new ArchiveTaskAction)($task);
            $this->fail('Expected RuntimeException');
        } catch (RuntimeException) {
            $this->assertNull($task->fresh()->archived_at);
            Event::assertNotDispatched(TaskArchived::class);
        }
    }

    public function test_unarchive_task_clears_archived_at_keeps_status_and_fires_event(): void
    {
        Event::fake([TaskUnarchived::class]);

        $task = Task::factory()->completed()->create(['archived_at' => now()]);

        (new UnarchiveTaskAction)($task);

        $fresh = $task->fresh();
        $this->assertNull($fresh->archived_at);
        $this->assertSame(TaskStatus::Done, $fresh->status);
        Event::assertDispatched(TaskUnarchived::class);
    }

    public function test_unarchive_task_rejects_tasks_that_are_not_archived(): void
    {
        Event::fake([TaskUnarchived::class]);

        $task = Task::factory()->completed()->create();

        try {
            (new UnarchiveTaskAction)($task);
            $this->fail('Expected RuntimeException');
        } catch (RuntimeException) {
            Event::assertNotDispatched(TaskUnarchived::class);
        }
    }
}
