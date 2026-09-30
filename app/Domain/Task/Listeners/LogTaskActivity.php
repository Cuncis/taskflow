<?php

namespace App\Domain\Task\Listeners;

use App\Domain\Task\Events\TaskCreated;
use App\Domain\Task\Jobs\LogTaskCreationJob;

class LogTaskActivity
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
        LogTaskCreationJob::dispatch($event->task);
    }
}
