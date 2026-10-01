<?php

namespace App\Domain\Task\Actions;

use App\Domain\Task\Events\TaskUnarchived;
use App\Domain\Task\Models\Task;
use RuntimeException;

class UnarchiveTaskAction
{
    public function __invoke(Task $task): Task
    {
        if ($task->archived_at === null) {
            throw new RuntimeException('Only archived tasks can be unarchived.');
        }

        // Status is left as-is: archiving requires 'done', so the task comes back done.
        // Reopening it is a separate, deliberate move (MoveTaskAction).
        $task->update(['archived_at' => null]);

        TaskUnarchived::dispatch($task);

        return $task;
    }
}
