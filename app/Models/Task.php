<?php

namespace App\Models;

use App\Observers\TaskObserver;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Domain\Task\TaskPriority;
use App\Models\Scopes\ExcludeArchivedProjectTasksScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

#[ObservedBy([TaskObserver::class])]
#[Fillable(['title', 'description', 'status', 'slug', 'project_id', 'due_date', 'assignee_id'])]
#[ScopedBy([ExcludeArchivedProjectTasksScope::class])]
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

    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->where('status', '!=', 'done')
            ->where('due_date', '<', now());
    }

    #[Scope]
    protected function assignedTo(Builder $query, User $user): void
    {
        $query->where('assignee_id', $user->id);
    }

    /** Not-done tasks due from today through $days days from now (inclusive; due_date is a DATE column). */
    #[Scope]
    protected function dueSoon(Builder $query, int $days = 3): void
    {
        $query->where('status', '!=', 'done')
            ->whereBetween('due_date', [today(), today()->addDays($days)]);
    }

    #[Scope]
    protected function priorityAtLeast(Builder $query, TaskPriority $minimum): void
    {
        $allowed = array_filter(
            TaskPriority::cases(),
            fn(TaskPriority $priority): bool => $priority->weight() >= $minimum->weight(),
        );

        $query->whereIn('priority', array_map(fn(TaskPriority $p): string => $p->value, $allowed));
    }

    /** @return array{priority: class-string<TaskPriority>, due_date: 'date'} */
    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'due_date' => 'date',
        ];
    }
}
