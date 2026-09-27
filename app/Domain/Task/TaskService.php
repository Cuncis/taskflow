<?php

namespace App\Domain\Task;

use App\Models\Task;
use Illuminate\Support\Facades\Log;

class TaskService
{

    public function __construct(
        private SlugGenerator $slugGenerator
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

        return $task;
    }
}
