<?php

namespace Tests\Feature;

use App\Domain\Project\Models\Project;
use App\Domain\Task\Models\Task;
use App\Domain\Task\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_active_tasks_for_a_project(): void
    {
        $project = Project::factory()->create();
        $active = Task::factory()->create(['project_id' => $project->id, 'status' => 'todo']);
        Task::factory()->completed()->create(['project_id' => $project->id]);

        $tasks = app(TaskService::class)->getActiveTasksForProject($project->id);

        $this->assertCount(1, $tasks);
        $this->assertTrue($tasks->first()->is($active));
    }
}
