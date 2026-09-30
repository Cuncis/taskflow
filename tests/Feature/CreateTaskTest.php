<?php

namespace Tests\Feature;

use App\Events\TaskCreated;
use App\Jobs\LogTaskCreationJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CreateTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fires_a_task_created_event_with_the_new_task(): void
    {
        // Fake only this event; a bare Event::fake() would swallow every framework event too.
        Event::fake([TaskCreated::class]);

        $this->postJson('/tasks', ['title' => 'Fix the login bug', 'status' => 'todo'])
            ->assertStatus(201);

        // The closure asserts on the payload, not just "some TaskCreated fired".
        Event::assertDispatched(
            TaskCreated::class,
            fn (TaskCreated $event) => $event->task->title === 'Fix the login bug',
        );
        Event::assertDispatchedTimes(TaskCreated::class, 1);
    }

    public function test_it_does_not_fire_a_task_created_event_when_validation_fails(): void
    {
        Event::fake([TaskCreated::class]);

        $this->postJson('/tasks', ['status' => 'todo']) // missing title
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');

        Event::assertNotDispatched(TaskCreated::class);
    }

    public function test_it_queues_a_job_to_log_task_creation(): void
    {
        // Events stay real here so the LogTaskActivity listener runs; only the queue is faked.
        Queue::fake();

        $this->postJson('/tasks', ['title' => 'Fix the login bug', 'status' => 'todo'])
            ->assertStatus(201);

        Queue::assertPushed(
            LogTaskCreationJob::class,
            fn (LogTaskCreationJob $job) => $job->task->title === 'Fix the login bug',
        );
    }
}
