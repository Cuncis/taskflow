<?php

namespace App\Domain\Task\Actions;

use App\Domain\Task\Events\TaskAssigned;
use App\Domain\Task\Models\Task;
use App\Models\User;

class AssignTaskAction
{
    public function __invoke(Task $task, User $assignee): Task
    {
        $task->update(['assignee_id' => $assignee->id]);

        TaskAssigned::dispatch($task, $assignee);

        return $task;
    }
}
