<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QueryCountingTest extends TestCase
{
    use RefreshDatabase;

    private const TASKS = 5;

    private function seedTasksWithComments(): void
    {
        Task::factory()->count(self::TASKS)->create()->each(
            fn (Task $task) => Comment::factory()->count(2)->for($task, 'commentable')->create()
        );
    }

    /** @param  callable(): mixed  $work */
    private function countQueries(callable $work): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $work();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_n_plus_one_without_eager_loading(): void
    {
        $this->seedTasksWithComments();

        // The app forbids lazy loading (AppServiceProvider); lift it to reproduce the mistake.
        Model::preventLazyLoading(false);

        $queries = $this->countQueries(function () {
            foreach (Task::whereHas('comments')->get() as $task) {
                $task->comments->count(); // lazy load: one query per task
            }
        });

        // 1 for the tasks + 1 per task for its comments.
        $this->assertSame(1 + self::TASKS, $queries);
    }

    public function test_eager_loading_fixes_n_plus_one(): void
    {
        $this->seedTasksWithComments();

        $queries = $this->countQueries(function () {
            foreach (Task::whereHas('comments')->with('comments')->get() as $task) {
                $task->comments->count();
            }
        });

        // 1 for the tasks + 1 for ALL comments, regardless of task count.
        $this->assertSame(2, $queries);
    }

    public function test_with_count_same_relation_twice_under_different_aliases(): void
    {
        $project = Project::factory()->create();
        $today = today();

        // 2 overdue, 1 overdue-but-done, 1 future, 1 with no due date => 5 tasks total, 2 overdue.
        Task::factory()->create(['project_id' => $project->id, 'status' => 'todo', 'due_date' => $today->copy()->subDays(3)]);
        Task::factory()->create(['project_id' => $project->id, 'status' => 'in_progress', 'due_date' => $today->copy()->subDay()]);
        Task::factory()->create(['project_id' => $project->id, 'status' => 'done', 'due_date' => $today->copy()->subDay()]);
        Task::factory()->create(['project_id' => $project->id, 'status' => 'todo', 'due_date' => $today->copy()->addDays(4)]);
        Task::factory()->create(['project_id' => $project->id, 'status' => 'todo', 'due_date' => null]);

        // A second project, to prove counts are per-project.
        $other = Project::factory()->create();
        Task::factory()->create(['project_id' => $other->id, 'status' => 'todo', 'due_date' => $today->copy()->subDay()]);

        $queries = $this->countQueries(function () use ($project, &$found) {
            $found = Project::withCount([
                'tasks',
                'tasks as overdue_tasks_count' => fn ($query) => $query->overdue(),
            ])->find($project->id);
        });

        $this->assertSame(1, $queries, 'both counts come from ONE query (correlated subqueries)');
        $this->assertSame(5, $found->tasks_count);
        $this->assertSame(2, $found->overdue_tasks_count);
    }
}
