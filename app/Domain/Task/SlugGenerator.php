<?php

namespace App\Domain\Task;

use App\Domain\Task\Repositories\TaskRepositoryInterface;
use Illuminate\Support\Str;

class SlugGenerator
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
    ) {}

    public function generateUniqueSlug(string $title, ?int $ignoreTaskId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while ($this->taskRepository->slugExists($slug, $ignoreTaskId)) {
            $slug = $originalSlug.'-'.$count;
            $count++;
        }

        return $slug;
    }
}
