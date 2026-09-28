<?php

namespace App\Domain\Task;

use App\Domain\Notification\NotificationChannel;
use App\Models\Task;
use Illuminate\Support\Facades\Log;

class TaskService
{

    public function __construct(
        private SlugGenerator $slugGenerator,
        private NotificationChannel $notificationChannel
    ) {}

    public function createTask(array $data): Task
    {
        $task = Task::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => $data['status'],
            'slug' => $this->slugGenerator->generateUniqueSlug($data['title'])
        ]);

        Log::info("Task created: {$task->title}", ['task_id' => $task->id]);

        $this->notificationChannel->send(
            to: 'team@taskflow.test',
            message: "New task created: {$task->title}"
        );

        return $task;
    }

    public function updateTask(Task $task, array $data): Task
    {
        $titleChanged = $data['title'] !== $task->title;

        $task->title = $data['title'];
        $task->description = $data['description'];
        $task->status = $data['status'];

        if ($titleChanged) {
            $task->slug = $this->slugGenerator->generateUniqueSlug($data['title'], $task->id);
        }

        $task->save();

        Log::info("Task updated: {$task->title}", ['task_id' => $task->id]);

        return $task;
    }
}
