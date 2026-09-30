<?php

namespace Tests\Feature;

use App\Domain\Project\Models\Project;
use App\Domain\Task\Models\Task;
use App\Domain\Task\TaskPriority;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class TaskScopesTest extends TestCase
{
    use RefreshDatabase;

    // ---- task scopes, built from named factory states ----------------------

    #[Group('task-scopes')]
    public function test_overdue_scope_only_returns_overdue_tasks(): void
    {
        Task::factory()->count(3)->overdue()->create();
        Task::factory()->count(2)->completed()->create();

        $this->assertSame(3, Task::overdue()->count());
    }

    #[Group('task-scopes')]
    public function test_priority_at_least_filters_by_minimum_priority(): void
    {
        Task::factory()->highPriority()->create();
        Task::factory()->create(['priority' => TaskPriority::Urgent]);
        Task::factory()->create(['priority' => TaskPriority::Low]);

        $this->assertSame(2, Task::priorityAtLeast(TaskPriority::High)->count());
    }

    #[Group('task-scopes')]
    public function test_assigned_to_finds_tasks_for_a_specific_user(): void
    {
        $user = User::factory()->create();

        Task::factory()->count(2)->create(['assignee_id' => $user->id]);
        Task::factory()->count(5)->create();
        Task::factory()->unassigned()->create();

        $this->assertSame(2, Task::assignedTo($user)->count());
    }

    #[Group('task-scopes')]
    public function test_urgent_state_is_overdue_and_high_priority(): void
    {
        $urgent = Task::factory()->count(4)->urgent()->create();

        $this->assertSame(4, Task::overdue()->priorityAtLeast(TaskPriority::High)->count());
        $urgent->each(fn (Task $task) => $this->assertSame(TaskPriority::High, $task->priority));
    }

    // ---- factory behavior itself -------------------------------------------

    #[Group('factories')]
    public function test_with_comments_attaches_exactly_the_requested_number(): void
    {
        foreach ([0, 1, 4] as $count) {
            $task = Task::factory()->withComments($count)->create();

            $this->assertSame($count, $task->comments()->count(), "withComments($count)");
        }
    }

    #[Group('factories')]
    public function test_sequence_cycles_through_priorities_and_wraps_around(): void
    {
        $tasks = Task::factory()
            ->count(9)
            ->state(new Sequence(
                ['priority' => TaskPriority::Low],
                ['priority' => TaskPriority::Medium],
                ['priority' => TaskPriority::High],
                ['priority' => TaskPriority::Urgent],
            ))
            ->create();

        $this->assertSame(
            ['low', 'medium', 'high', 'urgent', 'low', 'medium', 'high', 'urgent', 'low'],
            $tasks->map(fn (Task $task) => $task->priority->value)->all(),
        );
        $this->assertSame(TaskPriority::Low, $tasks->last()->priority); // the 9th wraps back to Low
    }

    #[Group('factories')]
    public function test_archived_project_tasks_are_hidden_by_the_global_scope(): void
    {
        $archived = Project::factory()->archived()->create();
        Task::factory()->count(2)->for($archived)->create();
        Task::factory()->count(3)->create();

        $this->assertNotNull($archived->archived_at);
        $this->assertSame(3, Task::count());
        $this->assertSame(5, Task::withoutGlobalScopes()->count());
    }

    // ---- the real seeder ---------------------------------------------------

    #[Group('factories')]
    public function test_database_seeder_builds_the_expected_demo_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, Project::count());
        $this->assertSame(1, Project::whereNotNull('archived_at')->count());
        $this->assertSame(6, User::count());
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);

        // 3 active projects x (8 + 4 + 3) tasks; the archived project's 5 are hidden by the global scope.
        $this->assertSame(45, Task::count());
        $this->assertSame(50, Task::withoutGlobalScopes()->count());

        // Every seeded task got a slug, i.e. model events (TaskObserver) really ran.
        $this->assertSame(0, Task::withoutGlobalScopes()->whereNull('slug')->orWhere('slug', '')->count());
        // At least the 12 explicit overdue() tasks (the 8 default ones may be overdue by chance too).
        $this->assertGreaterThanOrEqual(12, Task::overdue()->count());
    }
}
