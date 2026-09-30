<?php

namespace App\Domain\Task;

use App\Domain\Task\Models\Task;
use Illuminate\Support\Str;

class SlugGenerator
{
    public function generateUniqueSlug(string $title, ?int $ignoreTaskId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (
            Task::where('slug', $slug)
                ->when($ignoreTaskId, fn ($query) => $query->where('id', '!=', $ignoreTaskId))
                ->exists()
        ) {
            $slug = $originalSlug.'-'.$count;
            $count++;
        }

        return $slug;
    }
}
