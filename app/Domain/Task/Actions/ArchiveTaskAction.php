<?php

namespace App\Domain\Task\Actions;

use App\Domain\Task\Events\TaskArchived;
use App\Domain\Task\Models\Task;
use RuntimeException;

class ArchiveTaskAction
{
    public function __invoke(Task $task): Task
    {
        if ($task->status !== 'done') {
            throw new RuntimeException('Only completed tasks can be archived.');
        }

        $task->update(['archived_at' => now()]);

        TaskArchived::dispatch($task);

        return $task;
    }
}
