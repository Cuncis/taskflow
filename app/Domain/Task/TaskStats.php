<?php

namespace App\Domain\Task;

use App\Domain\Project\Models\Project;

class TaskStats
{
    public function completionRateFor(Project $project): float
    {
        $total = $project->tasks()->count();

        if ($total === 0) {
            return 0.0;
        }

        $completed = $project->tasks()->where('status', 'done')->count();

        return round(($completed / $total) * 100, 1);
    }

    public function overdueCountFor(Project $project): int
    {
        return $project->tasks()->overdue()->count();
    }
}
