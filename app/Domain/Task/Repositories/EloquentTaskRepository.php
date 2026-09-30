<?php

namespace App\Domain\Task\Repositories;

use App\Domain\Task\Models\Task;
use Illuminate\Database\Eloquent\Collection;

class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function find(int $id): ?Task
    {
        return Task::find($id);
    }

    public function create(array $data): Task
    {
        // forceFill: project_id is not in Task's #[Fillable] list.
        return tap((new Task)->forceFill($data))->save();
    }

    public function update(Task $task, array $data): Task
    {
        $task->forceFill($data)->save();

        return $task;
    }

    public function findActiveForProject(int $projectId): Collection
    {
        return Task::where('project_id', $projectId)->where('status', '!=', 'done')->get();
    }

    public function findOverdue(): Collection
    {
        return Task::overdue()->get();
    }

    public function slugExists(string $slug, ?int $ignoreTaskId = null): bool
    {
        return Task::where('slug', $slug)
            ->when($ignoreTaskId, fn ($query) => $query->where('id', '!=', $ignoreTaskId))
            ->exists();
    }
}
