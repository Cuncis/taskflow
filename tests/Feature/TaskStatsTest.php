<?php

namespace Tests\Feature;

use App\Domain\Project\Models\Project;
use App\Domain\Task\Facades\TaskStats as TaskStatsFacade;
use App\Domain\Task\Models\Task;
use App\Domain\Task\TaskStats;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_completion_rate_is_the_percentage_of_done_tasks(): void
    {
        $project = Project::factory()->create();
        Task::factory()->count(1)->completed()->create(['project_id' => $project->id]);
        Task::factory()->count(2)->create(['project_id' => $project->id, 'status' => 'todo']);

        $this->assertSame(33.3, app(TaskStats::class)->completionRateFor($project));
    }

    public function test_completion_rate_is_zero_for_a_project_without_tasks(): void
    {
        $this->assertSame(0.0, app(TaskStats::class)->completionRateFor(Project::factory()->create()));
    }

    public function test_overdue_count_ignores_done_and_future_tasks(): void
    {
        $project = Project::factory()->create();
        Task::factory()->overdue()->create(['project_id' => $project->id, 'status' => 'todo']);
        Task::factory()->overdue()->completed()->create(['project_id' => $project->id]);
        Task::factory()->create(['project_id' => $project->id, 'status' => 'todo', 'due_date' => now()->addWeek()]);

        $this->assertSame(1, app(TaskStats::class)->overdueCountFor($project));
    }

    public function test_it_is_bound_as_a_singleton(): void
    {
        $this->assertSame(app(TaskStats::class), app(TaskStats::class));
    }

    public function test_the_facade_forwards_static_calls_to_the_container_instance(): void
    {
        $project = Project::factory()->create();
        Task::factory()->completed()->create(['project_id' => $project->id]);

        $this->assertSame(100.0, TaskStatsFacade::completionRateFor($project));
        $this->assertSame(app(TaskStats::class), TaskStatsFacade::getFacadeRoot());
    }

    public function test_the_facade_can_be_mocked(): void
    {
        $project = Project::factory()->create();

        TaskStatsFacade::shouldReceive('overdueCountFor')->once()->with($project)->andReturn(42);

        $this->assertSame(42, TaskStatsFacade::overdueCountFor($project));
    }

    // Injection style: the real class is built from the container and called as an object, so the test
    // exercises real behaviour and the dependency is explicit, not hidden behind a static call (see GetProjectSummaryActionInjected).
    public function test_average_tasks_per_assignee_ignores_unassigned_tasks(): void
    {
        $project = Project::factory()->create();
        [$alice, $bob] = User::factory()->count(2)->create();

        Task::factory()->count(3)->create(['project_id' => $project->id, 'assignee_id' => $alice->id]);
        Task::factory()->count(1)->create(['project_id' => $project->id, 'assignee_id' => $bob->id]);
        Task::factory()->unassigned()->count(5)->create(['project_id' => $project->id]);

        $stats = app(TaskStats::class);

        $this->assertSame(2.0, $stats->averageTasksPerAssignee($project)); // 4 assigned tasks / 2 people
        $this->assertSame(0.0, $stats->averageTasksPerAssignee(Project::factory()->create()));
    }
}
