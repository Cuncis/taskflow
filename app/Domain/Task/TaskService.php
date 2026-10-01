<?php

namespace App\Domain\Task;

use App\Domain\Task\Models\Task;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Queries only. Commands (anything that changes state or fires events) live in Actions/.
 */
class TaskService
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
    ) {}

    /**
     * @return Collection<int, Task>
     */
    public function getActiveTasksForProject(int $projectId): Collection
    {
        return $this->taskRepository->findActiveForProject($projectId);
    }
}
