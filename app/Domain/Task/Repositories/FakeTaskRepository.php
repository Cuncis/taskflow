<?php

namespace App\Domain\Task\Repositories;

use App\Domain\Task\TaskRepositoryInterface;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

/**
 * In-memory TaskRepositoryInterface for tests: no database needed.
 */
class FakeTaskRepository implements TaskRepositoryInterface
{
    /** @var array<int, Task> */
    private array $tasks = [];

    private int $lastId = 0;

    public function find(int $id): ?Task
    {
        return $this->tasks[$id] ?? null;
    }

    public function create(array $data): Task
    {
        // forceFill so project_id is kept even though Task's #[Fillable] list omits it.
        $task = (new Task)->forceFill($data);
        $task->id = ++$this->lastId;
        $task->exists = true;

        return $this->tasks[$task->id] = $task;
    }

    public function update(Task $task, array $data): Task
    {
        $task->forceFill($data);

        return $this->tasks[$task->id] = $task;
    }

    public function findActiveForProject(int $projectId): Collection
    {
        return new Collection(array_values(array_filter(
            $this->tasks,
            fn (Task $task): bool => $task->project_id === $projectId && $task->status !== 'done',
        )));
    }

    public function findOverdue(): Collection
    {
        return new Collection(array_values(array_filter(
            $this->tasks,
            fn (Task $task): bool => $task->status !== 'done'
                && $task->due_date !== null
                && $task->due_date->isPast(),
        )));
    }

    public function slugExists(string $slug, ?int $ignoreTaskId = null): bool
    {
        foreach ($this->tasks as $task) {
            if ($task->slug === $slug && $task->id !== $ignoreTaskId) {
                return true;
            }
        }

        return false;
    }
}
