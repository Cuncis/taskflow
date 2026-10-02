<?php

namespace App\Domain\Task\Models;

use App\Domain\Collaboration\Models\Attachment;
use App\Domain\Collaboration\Models\Comment;
use App\Domain\Project\Models\Project;
use App\Domain\Task\Observers\TaskObserver;
use App\Domain\Task\Scopes\ExcludeArchivedProjectTasksScope;
use App\Domain\Task\TaskPriority;
use App\Domain\Task\TaskStatus;
use App\Models\User;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

#[ObservedBy([TaskObserver::class])]
#[Fillable(['title', 'description', 'status', 'slug', 'project_id', 'due_date', 'assignee_id', 'archived_at'])]
#[ScopedBy([ExcludeArchivedProjectTasksScope::class])]
#[UseFactory(TaskFactory::class)]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return MorphMany<Comment, $this> */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /** @return MorphOne<Comment, $this> */
    public function latestComment(): MorphOne
    {
        return $this->morphOne(Comment::class, 'commentable')->latestOfMany();
    }

    /** @return MorphMany<Attachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /** @param  Builder<Task>  $query */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->where('status', '!=', TaskStatus::Done)
            ->where('due_date', '<', now());
    }

    /** @param  Builder<Task>  $query */
    #[Scope]
    protected function assignedTo(Builder $query, User $user): void
    {
        $query->where('assignee_id', $user->id);
    }

    /**
     * Not-done tasks due from today through $days days from now (inclusive; due_date is a DATE column).
     *
     * @param  Builder<Task>  $query
     */
    #[Scope]
    protected function dueSoon(Builder $query, int $days = 3): void
    {
        $query->where('status', '!=', TaskStatus::Done)
            ->whereBetween('due_date', [today(), today()->addDays($days)]);
    }

    /** @param  Builder<Task>  $query */
    #[Scope]
    protected function priorityAtLeast(Builder $query, TaskPriority $minimum): void
    {
        $allowed = array_filter(
            TaskPriority::cases(),
            fn (TaskPriority $priority): bool => $priority->weight() >= $minimum->weight(),
        );

        $query->whereIn('priority', array_map(fn (TaskPriority $p): string => $p->value, $allowed));
    }

    /** @return array{priority: 'App\Domain\Task\TaskPriority', status: 'App\Domain\Task\TaskStatus', due_date: 'date', archived_at: 'datetime'} */
    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'due_date' => 'date',
            'archived_at' => 'datetime',
        ];
    }
}
