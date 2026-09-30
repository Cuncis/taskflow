<?php

namespace App\Domain\Task\Events;

use App\Domain\Task\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Task $task) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
