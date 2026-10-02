<?php

namespace Tests\Feature;

use App\Domain\Project\Actions\GetProjectSummaryAction;
use App\Domain\Project\Actions\GetProjectSummaryActionInjected;
use App\Domain\Project\Models\Project;
use App\Domain\Task\Facades\TaskStats as TaskStatsFacade;
use App\Domain\Task\TaskStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GetProjectSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_a_project_summary_using_the_facade(): void
    {
        $project = Project::factory()->create();

        TaskStatsFacade::shouldReceive('completionRateFor')->once()->with($project)->andReturn(75.0);

        $summary = (new GetProjectSummaryAction)($project);

        $this->assertSame(75.0, $summary['completion_rate']);
        $this->assertSame($project->name, $summary['name']);
    }

    public function test_it_returns_a_project_summary_using_injected_task_stats(): void
    {
        $project = Project::factory()->create();

        $mockStats = Mockery::mock(TaskStats::class);
        $mockStats->shouldReceive('completionRateFor')->once()->with($project)->andReturn(75.0);

        $summary = (new GetProjectSummaryActionInjected($mockStats))($project);

        $this->assertSame(75.0, $summary['completion_rate']);
        $this->assertSame($project->name, $summary['name']);
    }
}
