<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Jobs\LogTaskCreationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
