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
     * Runs before the UPDATE. Only a changed title gets a new slug, and a slug
     * set explicitly in the same save is left alone.
     */
    public function updating(Task $task): void
    {
        if ($task->isDirty('title') && ! $task->isDirty('slug')) {
            $task->slug = $this->slugGenerator->generateUniqueSlug($task->title, $task->id);
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
        $task->comments()->delete();
        $task->attachments()->delete();
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
