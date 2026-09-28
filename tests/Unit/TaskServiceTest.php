<?php

namespace Tests\Unit;

use App\Domain\Task\Repositories\FakeTaskRepository;
use App\Domain\Task\TaskService;
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
}
