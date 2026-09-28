<?php

namespace App\Observers;

use App\Domain\Task\SlugGenerator;
use App\Models\Task;

class TaskObserver
{
    public function __construct(
        private SlugGenerator $slugGenerator
    ) {}
    /**
     * Runs before the INSERT, so the NOT NULL slug column gets its value.
     */
    public function creating(Task $task): void
    {
        if (empty($task->slug)) {
            $task->slug = $this->slugGenerator->generateUniqueSlug($task->title);
        }
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "force deleted" event.
     */
    public function forceDeleted(Task $task): void
    {
        //
    }
}
