<?php

namespace Tests\Feature;

use App\Domain\Notification\NotificationChannel;
use App\Events\TaskCreated;
use App\Jobs\LogTaskCreationJob;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CreateTaskTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /** PHPUnit's beforeEach: a fresh logged-in user for every test. */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_it_allows_a_logged_in_user_to_create_a_task(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->user)->postJson('/tasks', [
            'title' => 'Fix the login bug',
            'status' => 'todo',
            'project_id' => $project->id,
        ])
            ->assertStatus(201)
            ->assertJson(['message' => 'Task created successfully']);

        // The row really exists, not just a response that claims success.
        $this->assertDatabaseHas('tasks', ['title' => 'Fix the login bug', 'status' => 'todo']);
    }

    public function test_it_rejects_an_unauthenticated_user(): void
    {
        $this->postJson('/tasks', ['title' => 'Should not work', 'status' => 'todo'])
            ->assertStatus(401);

        $this->assertDatabaseMissing('tasks', ['title' => 'Should not work']);
    }

    public function test_it_rejects_a_task_with_no_title(): void
    {
        $this->actingAs($this->user)->postJson('/tasks', ['status' => 'todo'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_it_rejects_an_invalid_status_value(): void
    {
        $this->actingAs($this->user)->postJson('/tasks', ['title' => 'Valid title', 'status' => 'not_a_real_status'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseMissing('tasks', ['title' => 'Valid title']);
    }

    public function test_it_fires_a_task_created_event_with_the_new_task(): void
    {
        // Fake only this event; a bare Event::fake() would swallow every framework event too.
        Event::fake([TaskCreated::class]);

        $this->actingAs($this->user)->postJson('/tasks', ['title' => 'Fix the login bug', 'status' => 'todo'])
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

        $this->actingAs($this->user)->postJson('/tasks', ['status' => 'todo']) // missing title
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');

        Event::assertNotDispatched(TaskCreated::class);
    }

    public function test_it_queues_a_job_to_log_task_creation(): void
    {
        // Events stay real here so the LogTaskActivity listener runs; only the queue is faked.
        Queue::fake();
        // The other TaskCreated listeners run for real too; keep the notification channel from sending.
        $this->mock(NotificationChannel::class)->shouldIgnoreMissing();

        $this->actingAs($this->user)->postJson('/tasks', ['title' => 'Fix the login bug', 'status' => 'todo'])
            ->assertStatus(201);

        Queue::assertPushed(
            LogTaskCreationJob::class,
            fn (LogTaskCreationJob $job) => $job->task->title === 'Fix the login bug',
        );
    }

    public function test_it_does_not_queue_the_log_job_when_validation_fails(): void
    {
        // Events stay real: if validation ever let the request through, the listener would queue the job.
        Queue::fake();

        $this->actingAs($this->user)->postJson('/tasks', ['status' => 'todo']) // missing title
            ->assertStatus(422);

        Queue::assertNotPushed(LogTaskCreationJob::class);
        Queue::assertNothingPushed();
    }
}
