<?php

namespace App\Domain\Task\Actions;

use App\Domain\Task\Events\TaskMoved;
use App\Domain\Task\Models\Task;
use InvalidArgumentException;

class MoveTaskAction
{
    private const VALID_STATUSES = ['todo', 'in_progress', 'done'];

    public function __invoke(Task $task, string $toStatus): Task
    {
        if (! in_array($toStatus, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid status: {$toStatus}");
        }

        $fromStatus = $task->status;

        if ($fromStatus === $toStatus) {
            return $task; // no-op, nothing actually moved
        }

        $task->update(['status' => $toStatus]);

        TaskMoved::dispatch($task, $fromStatus, $toStatus);

        return $task;
    }
}
