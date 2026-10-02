<?php

namespace App\Domain\Project\Actions;

use App\Domain\Project\Models\Project;
use App\Domain\Task\Facades\TaskStats;

class GetProjectSummaryAction
{
    public function __invoke(Project $project): array
    {
        return [
            'name' => $project->name,
            'completion_rate' => TaskStats::completionRateFor($project),
        ];
    }
}
