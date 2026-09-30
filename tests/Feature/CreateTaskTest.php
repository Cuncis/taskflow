<?php

namespace Tests\Feature;

use App\Domain\Notification\NotificationChannel;
use App\Domain\Project\Models\Project;
use App\Domain\Task\Events\TaskCreated;
use App\Domain\Task\Jobs\LogTaskCreationJob;
use App\Domain\Task\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
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

    // ---------------------------------------------------------------
    // Happy path: valid requests succeed and trigger the right side effects  (--group=happy-path)
    // ---------------------------------------------------------------

    #[Group('happy-path')]
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
        $this->assertDatabaseHas('tasks', [
            'title' => 'Fix the login bug',
            'status' => 'todo',
            'project_id' => $project->id, // used to be silently dropped: no validation rule stripped it
        ]);
    }

    #[Group('happy-path')]
    public function test_project_id_is_optional(): void
    {
        $this->actingAs($this->user)->postJson('/tasks', ['title' => 'Loose task', 'status' => 'todo'])
            ->assertStatus(201);

        $this->assertDatabaseHas('tasks', ['title' => 'Loose task', 'project_id' => null]);
    }

    #[Group('happy-path')]
    public function test_description_is_optional_and_saved_as_null_when_omitted(): void
    {
        // No 'description' key at all, not even an empty string.
        $payload = ['title' => 'No description here', 'status' => 'todo'];
        $this->assertArrayNotHasKey('description', $payload);

        $this->actingAs($this->user)->postJson('/tasks', $payload)
            ->assertStatus(201);

        $this->assertDatabaseHas('tasks', [
            'title' => 'No description here',
            'description' => null, // assertDatabaseHas turns null into "IS NULL"
        ]);
        $this->assertNull(Task::where('title', 'No description here')->firstOrFail()->description);
    }

    #[Group('happy-path')]
    public function test_it_accepts_a_title_of_exactly_the_maximum_length(): void
    {
        $title = str_repeat('a', 255);
        $this->assertSame(255, strlen($title));

        $this->actingAs($this->user)->postJson('/tasks', ['title' => $title, 'status' => 'todo'])
            ->assertStatus(201);

        $this->assertDatabaseHas('tasks', ['title' => $title]);
    }

    #[Group('happy-path')]
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

    #[Group('happy-path')]
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

    // ---------------------------------------------------------------
    // Validation failures: bad input gets a 422, saves nothing and triggers no side effects  (--group=validation-failure)
    // ---------------------------------------------------------------

    #[Group('validation-failure')]
    public function test_it_rejects_a_task_with_no_title(): void
    {
        $this->actingAs($this->user)->postJson('/tasks', ['status' => 'todo'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    #[Group('validation-failure')]
    public function test_it_rejects_a_title_one_character_over_the_limit(): void
    {
        $title = str_repeat('a', 256);
        $this->assertSame(256, strlen($title));

        $this->actingAs($this->user)->postJson('/tasks', ['title' => $title, 'status' => 'todo'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title'])
            ->assertJsonMissingValidationErrors(['status']);

        $this->assertDatabaseMissing('tasks', ['title' => $title]);
    }

    #[Group('validation-failure')]
    public function test_it_rejects_a_project_id_that_does_not_exist(): void
    {
        $this->actingAs($this->user)->postJson('/tasks', [
            'title' => 'Orphan task',
            'status' => 'todo',
            'project_id' => 999999,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['project_id']);

        $this->assertDatabaseMissing('tasks', ['title' => 'Orphan task']);
    }

    #[Group('validation-failure')]
    public function test_it_rejects_an_invalid_status_value(): void
    {
        $this->actingAs($this->user)->postJson('/tasks', ['title' => 'Valid title', 'status' => 'not_a_real_status'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseMissing('tasks', ['title' => 'Valid title']);
    }

    #[Group('validation-failure')]
    public function test_it_does_not_fire_a_task_created_event_when_validation_fails(): void
    {
        Event::fake([TaskCreated::class]);

        $this->actingAs($this->user)->postJson('/tasks', ['status' => 'todo']) // missing title
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');

        Event::assertNotDispatched(TaskCreated::class);
    }

    #[Group('validation-failure')]
    public function test_it_does_not_queue_the_log_job_when_validation_fails(): void
    {
        // Events stay real: if validation ever let the request through, the listener would queue the job.
        Queue::fake();

        $this->actingAs($this->user)->postJson('/tasks', ['status' => 'todo']) // missing title
            ->assertStatus(422);

        Queue::assertNotPushed(LogTaskCreationJob::class);
        Queue::assertNothingPushed();
    }

    // ---------------------------------------------------------------
    // Authentication: anonymous requests never reach the controller  (--group=auth)
    // ---------------------------------------------------------------

    #[Group('auth')]
    public function test_it_rejects_an_unauthenticated_user(): void
    {
        $this->postJson('/tasks', ['title' => 'Should not work', 'status' => 'todo'])
            ->assertStatus(401);

        $this->assertDatabaseMissing('tasks', ['title' => 'Should not work']);
    }
}
