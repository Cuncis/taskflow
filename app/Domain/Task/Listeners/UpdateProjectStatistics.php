<?php

namespace App\Domain\Task\Listeners;

use App\Domain\Task\Events\TaskCreated;
use Illuminate\Support\Facades\Log;

class UpdateProjectStatistics
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TaskCreated $event): void
    {
        Log::info('Project statistics updated', [
            'project_id' => $event->task->project_id,
        ]);
    }
}
