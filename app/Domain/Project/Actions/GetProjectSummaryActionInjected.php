<?php

namespace App\Domain\Project\Actions;

use App\Domain\Project\Models\Project;
use App\Domain\Task\TaskStats;

class GetProjectSummaryActionInjected
{
    public function __construct(
        private TaskStats $taskStats,
    ) {}

    public function __invoke(Project $project): array
    {
        return [
            'name' => $project->name,
            'completion_rate' => $this->taskStats->completionRateFor($project),
        ];
    }
}
