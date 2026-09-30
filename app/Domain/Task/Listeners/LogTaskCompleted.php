<?php

namespace App\Domain\Task\Listeners;

use App\Domain\Task\Events\TaskCompleted;
use Illuminate\Support\Facades\Log;

class LogTaskCompleted
{
    public function handle(TaskCompleted $event): void
    {
        Log::info('Task completed.', [
            'task_id' => $event->task->id,
        ]);
    }
}
