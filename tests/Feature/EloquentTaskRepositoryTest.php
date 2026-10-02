<?php

namespace Tests\Feature;

use App\Domain\Project\Models\Project;
use App\Domain\Task\Models\Task;
use App\Domain\Task\Repositories\EloquentTaskRepository;
use App\Domain\Task\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentTaskRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentTaskRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentTaskRepository;
    }

    public function test_find_returns_the_task_or_null(): void
    {
        $task = Task::factory()->create();

        $this->assertTrue($this->repository->find($task->id)->is($task));
        $this->assertNull($this->repository->find(999999));
    }

    public function test_create_persists_a_task_including_its_project_id(): void
    {
        $project = Project::factory()->create();

        $task = $this->repository->create(['title' => 'Repo task', 'description' => null, 'status' => 'todo', 'project_id' => $project->id]);

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Repo task', 'project_id' => $project->id]);
    }

    public function test_update_persists_changes(): void
    {
        $task = Task::factory()->create(['status' => 'todo']);

        $this->repository->update($task, ['status' => TaskStatus::Done]);

        $this->assertSame(TaskStatus::Done, $task->fresh()->status);
    }

    public function test_find_active_for_project_excludes_done_tasks_and_other_projects(): void
    {
        $project = Project::factory()->create();
        $active = Task::factory()->create(['project_id' => $project->id, 'status' => 'in_progress']);
        Task::factory()->completed()->create(['project_id' => $project->id]);
        Task::factory()->create(['project_id' => Project::factory()->create()->id, 'status' => 'todo']);

        $found = $this->repository->findActiveForProject($project->id);

        $this->assertCount(1, $found);
        $this->assertTrue($found->first()->is($active));
    }

    public function test_find_overdue_returns_only_overdue_not_done_tasks(): void
    {
        $overdue = Task::factory()->overdue()->create(['status' => 'todo']);
        Task::factory()->overdue()->completed()->create();
        Task::factory()->create(['status' => 'todo', 'due_date' => now()->addWeek()]);

        $found = $this->repository->findOverdue();

        $this->assertCount(1, $found);
        $this->assertTrue($found->first()->is($overdue));
    }

    public function test_slug_exists_can_ignore_a_given_task(): void
    {
        $task = Task::factory()->create(['title' => 'Unique title']);

        $this->assertTrue($this->repository->slugExists($task->slug));
        $this->assertFalse($this->repository->slugExists($task->slug, $task->id));
        $this->assertFalse($this->repository->slugExists('no-such-slug'));
    }
}
