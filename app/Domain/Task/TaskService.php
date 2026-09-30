<?php

namespace App\Domain\Task;

use App\Domain\Task\Events\TaskCompleted;
use App\Domain\Task\Events\TaskCreated;
use App\Domain\Task\Models\Task;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class TaskService
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
    ) {}

    public function createTask(array $data): Task
    {
        $task = $this->taskRepository->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'project_id' => $data['project_id'] ?? null,
        ]);

        Log::info("Task created: {$task->title}", ['task_id' => $task->id]);

        TaskCreated::dispatch($task);

        return $task;
    }

    public function updateTask(Task $task, array $data): Task
    {
        $titleChanged = $data['title'] !== $task->title;

        $task->title = $data['title'];
        $task->description = $data['description'];
        $task->status = $data['status'];
        $task->project_id = $data['project_id'];

        $task->save();

        Log::info("Task updated: {$task->title}", ['task_id' => $task->id]);

        return $task;
    }

    public function completeTask(Task $task): Task
    {
        $task = $this->taskRepository->update($task, ['status' => 'done']);

        TaskCompleted::dispatch($task);

        return $task;
    }

    /**
     * @return Collection<int, Task>
     */
    public function getActiveTasksForProject(int $projectId): Collection
    {
        return $this->taskRepository->findActiveForProject($projectId);
    }
}
