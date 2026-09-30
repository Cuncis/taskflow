<?php

namespace Database\Seeders;

use App\Domain\Project\Models\Project;
use App\Domain\Task\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Deliberately NOT using WithoutModelEvents: TaskObserver::creating fills the NOT NULL slug column.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::factory()->count(5)->create()->push(
            User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']),
        );

        $activeProjects = Project::factory()
            ->count(3)
            ->has(Task::factory()->count(8)->withRandomComments()->recycle($users), 'tasks')
            ->has(Task::factory()->count(4)->overdue()->withRandomComments()->recycle($users), 'tasks')
            ->has(Task::factory()->count(3)->completed()->withRandomComments()->recycle($users), 'tasks')
            ->create();

        Project::factory()
            ->archived()
            ->has(Task::factory()->count(5)->completed()->recycle($users), 'tasks')
            ->create();

        $this->command?->info('Seeded '.$activeProjects->count().' active projects and 1 archived project.');
    }
}
