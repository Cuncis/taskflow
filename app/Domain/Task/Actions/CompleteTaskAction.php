<?php

namespace App\Domain\Task\Actions;

use App\Domain\Task\Events\TaskCompleted;
use App\Domain\Task\Models\Task;
use App\Domain\Task\Repositories\TaskRepositoryInterface;

class CompleteTaskAction
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
    ) {}

    public function __invoke(Task $task): Task
    {
        $task = $this->taskRepository->update($task, ['status' => 'done']);

        TaskCompleted::dispatch($task);

        return $task;
    }
}
