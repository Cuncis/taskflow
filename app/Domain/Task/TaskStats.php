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

        $completed = $project->tasks()->where('status', TaskStatus::Done)->count();

        return round(($completed / $total) * 100, 1);
    }

    public function overdueCountFor(Project $project): int
    {
        return $project->tasks()->overdue()->count();
    }

    /** Assigned tasks divided by the number of distinct people holding them; unassigned tasks are ignored. */
    public function averageTasksPerAssignee(Project $project): float
    {
        $assigned = $project->tasks()->whereNotNull('assignee_id');

        $assignedTasks = (clone $assigned)->count();

        if ($assignedTasks === 0) {
            return 0.0;
        }

        $assignees = $assigned->distinct()->count('assignee_id');

        return round($assignedTasks / $assignees, 1);
    }
}
