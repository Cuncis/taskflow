<?php

namespace App\Jobs;

use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class LogTaskCreationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Task $task,
    ) {}

    public function handle(): void
    {
        Log::info("[Queued] Task created: {$this->task->title}");
    }
}
