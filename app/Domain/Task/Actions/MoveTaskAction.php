<?php

namespace App\Domain\Task\Actions;

use App\Domain\Task\Events\TaskMoved;
use App\Domain\Task\Models\Task;
use App\Domain\Task\TaskStatus;

class MoveTaskAction
{
    public function __invoke(Task $task, TaskStatus $toStatus): Task
    {
        $fromStatus = $task->status;

        if ($fromStatus === $toStatus) {
            return $task; // no-op, nothing actually moved
        }

        $task->update(['status' => $toStatus]);

        TaskMoved::dispatch($task, $fromStatus->value, $toStatus->value);

        return $task;
    }
}
