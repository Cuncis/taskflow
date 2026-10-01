<?php

namespace App\Domain\Task\Events;

use App\Domain\Task\Models\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskUnarchived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Task $task,
    ) {}
}
