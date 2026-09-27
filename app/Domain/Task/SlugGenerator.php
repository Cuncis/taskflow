<?php

namespace App\Domain\Task;

use App\Models\Task;
use Illuminate\Support\Str;

class SlugGenerator
{

    public function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (Task::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
