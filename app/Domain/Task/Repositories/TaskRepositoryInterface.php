<?php

namespace App\Domain\Task\Repositories;

use App\Domain\Task\Models\Task;
use Illuminate\Database\Eloquent\Collection;

interface TaskRepositoryInterface
{
    public function find(int $id): ?Task;

    /**
     * @param  array{title: string, description: ?string, status: string, project_id: ?int, slug?: string}  $data
     */
    public function create(array $data): Task;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Task $task, array $data): Task;

    /**
     * @return Collection<int, Task>
     */
    public function findActiveForProject(int $projectId): Collection;

    public function slugExists(string $slug, ?int $ignoreTaskId = null): bool;

    /**
     * @return Collection<int, Task>
     */
    public function findOverdue(): Collection;
}
