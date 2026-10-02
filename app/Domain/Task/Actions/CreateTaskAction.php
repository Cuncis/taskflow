<?php

namespace App\Domain\Task\Actions;

use App\Domain\Task\Events\TaskCreated;
use App\Domain\Task\Models\Task;
use App\Domain\Task\Repositories\TaskRepositoryInterface;

class CreateTaskAction
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function __invoke(array $data): Task
    {
        $task = $this->taskRepository->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'project_id' => $data['project_id'] ?? null,
            'assignee_id' => $data['assignee_id'] ?? null,
        ]);

        TaskCreated::dispatch($task);

        return $task;
    }
}
