<?php

namespace App\Domain\Project\Models;

use App\Domain\Collaboration\Models\Attachment;
use App\Domain\Collaboration\Models\Comment;
use App\Domain\Task\Models\Task;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['name', 'description'])]
#[UseFactory(ProjectFactory::class)]
class Project extends Model
{
    use HasFactory;

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** @return MorphMany<Comment, $this> */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /** @return MorphMany<Attachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /** @return HasManyThrough<Comment, Task, $this> */
    public function taskComments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Comment::class,
            Task::class,
            'project_id',      // foreign key on the middle table (tasks.project_id)
            'commentable_id',  // foreign key on the final table (comments.commentable_id)
        )->where('comments.commentable_type', 'task');
    }
}
