<?php

namespace App\Domain\Task;

use App\Domain\Notification\NotificationChannel;
use App\Events\TaskCreated;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class TaskService
{

    public function __construct(
        private TaskRepositoryInterface $taskRepository,
    ) {}

    public function createTask(array $data): Task
    {
        $task = Task::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => $data['status'],
            'project_id' => $data['project_id'] ?? null
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

    /**
     * @return Collection<int, Task>
     */
    public function getActiveTasksForProject(int $projectId): Collection
    {
        return $this->taskRepository->findActiveForProject($projectId);
    }
}
