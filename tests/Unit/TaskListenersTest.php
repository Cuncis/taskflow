<?php

namespace Tests\Unit;

use App\Domain\Task\Events\TaskCompleted;
use App\Domain\Task\Events\TaskCreated;
use App\Domain\Task\Jobs\LogTaskCreationJob;
use App\Domain\Task\Listeners\LogTaskActivity;
use App\Domain\Task\Listeners\LogTaskCompleted;
use App\Domain\Task\Listeners\UpdateProjectStatistics;
use App\Domain\Task\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TaskListenersTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_task_completed_logs_the_task_id(): void
    {
        $task = Task::factory()->completed()->create();

        Log::shouldReceive('info')->once()->with('Task completed.', ['task_id' => $task->id]);

        (new LogTaskCompleted)->handle(new TaskCompleted($task));
    }

    public function test_update_project_statistics_logs_the_project_id(): void
    {
        $task = Task::factory()->create();

        Log::shouldReceive('info')->once()->with('Project statistics updated', ['project_id' => $task->project_id]);

        (new UpdateProjectStatistics)->handle(new TaskCreated($task));
    }

    public function test_log_task_activity_queues_the_logging_job(): void
    {
        Queue::fake();
        $task = Task::factory()->create();

        (new LogTaskActivity)->handle(new TaskCreated($task));

        Queue::assertPushed(LogTaskCreationJob::class, fn (LogTaskCreationJob $job) => $job->task->is($task));
    }
}
