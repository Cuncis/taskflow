<?php

namespace App\Domain\Task\Events;

use App\Domain\Task\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly User $assignee,
    ) {}
}
