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
        return Task::active()->where('project_id', $projectId)->get();
    }

    /**
     * @return Collection<int, Task>
     */
    public function findOverdue(): Collection
    {
        return Task::overdue()->get();
    }

    public function slugExists(string $slug, ?int $ignoreTaskId = null): bool
    {
        // Slugs are unique across ALL tasks, including ones hidden by the archived-project global scope.
        return Task::withoutGlobalScopes()->where('slug', $slug)
            ->when($ignoreTaskId, fn ($query) => $query->where('id', '!=', $ignoreTaskId))
            ->exists();
    }
}
