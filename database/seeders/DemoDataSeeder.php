<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::factory()->count(5)->create();
        $projects = Project::factory()->count(5)->create();

        $tasks = Task::factory()
            ->count(40)
            ->recycle($projects)
            ->recycle($users)
            ->create();

        foreach ($tasks as $task) {
            Comment::factory()
                ->count(fake()->numberBetween(0, 3))
                ->recycle($users)
                ->for($task, 'commentable')
                ->create();
        }
    }
}
