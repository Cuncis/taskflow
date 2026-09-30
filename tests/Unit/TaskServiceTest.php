<?php

namespace Tests\Unit;

use App\Domain\Task\Repositories\FakeTaskRepository;
use App\Domain\Task\TaskService;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    public function test_it_creates_a_task_and_announces_it(): void
    {
        Event::fake([TaskCreated::class]);

        $service = new TaskService(new FakeTaskRepository());

        $task = $service->createTask(['title' => 'Isolated test task', 'status' => 'todo', 'project_id' => 1]);

        $this->assertSame('Isolated test task', $task->title);

        Event::assertDispatched(TaskCreated::class);
    }

    public function test_it_completes_a_task_and_announces_it(): void
    {
        // TaskCreated is faked too: the setup createTask() must not run real listeners
        // (they'd queue a job that reloads the never-persisted in-memory task).
        Event::fake([TaskCreated::class, TaskCompleted::class]);

        $service = new TaskService(new FakeTaskRepository());
        $task = $service->createTask(['title' => 'Finish me', 'status' => 'in_progress']);

        $completed = $service->completeTask($task);

        $this->assertSame('done', $completed->status);
        Event::assertDispatched(TaskCompleted::class, fn (TaskCompleted $event) => $event->task->is($completed));
    }
}
