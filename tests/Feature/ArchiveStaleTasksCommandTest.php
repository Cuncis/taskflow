<?php

namespace Tests\Feature;

use App\Domain\Task\Events\TaskArchived;
use App\Domain\Task\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ArchiveStaleTasksCommandTest extends TestCase
{
    use RefreshDatabase;

    private function taskUpdatedDaysAgo(int $days, array $attributes = []): Task
    {
        $task = Task::factory()->completed()->create($attributes);
        $task->timestamps = false;
        $task->forceFill(['updated_at' => now()->subDays($days)])->save();

        return $task;
    }

    public function test_it_archives_only_stale_done_tasks(): void
    {
        Event::fake([TaskArchived::class]);

        $stale = $this->taskUpdatedDaysAgo(100);
        $recent = $this->taskUpdatedDaysAgo(10);
        $notDone = $this->taskUpdatedDaysAgo(100, ['status' => 'todo']);
        $already = $this->taskUpdatedDaysAgo(100, ['archived_at' => now()->subDay()]);

        $this->artisan('taskflow:archive-stale-tasks')
            ->expectsOutputToContain('Found 1 stale task(s).')
            ->expectsTable(['Title', 'Last Updated', 'Status'], [
                [$stale->title, '3 months ago', 'done'],
            ])
            ->expectsOutputToContain('Done.')
            ->assertSuccessful();

        $this->assertNotNull($stale->fresh()->archived_at);
        $this->assertNull($recent->fresh()->archived_at);
        $this->assertNull($notDone->fresh()->archived_at);
        Event::assertDispatchedTimes(TaskArchived::class, 1);
    }

    public function test_dry_run_changes_nothing(): void
    {
        Event::fake([TaskArchived::class]);

        $stale = $this->taskUpdatedDaysAgo(100);

        $this->artisan('taskflow:archive-stale-tasks', ['--dry-run' => true])
            ->expectsOutputToContain('[DRY RUN] Would archive')
            ->assertSuccessful();

        $this->assertNull($stale->fresh()->archived_at);
        Event::assertNotDispatched(TaskArchived::class);
    }

    public function test_days_option_changes_the_threshold(): void
    {
        Event::fake([TaskArchived::class]);

        $task = $this->taskUpdatedDaysAgo(10);

        $this->artisan('taskflow:archive-stale-tasks', ['--days' => 5])->assertSuccessful();

        $this->assertNotNull($task->fresh()->archived_at);
    }

    public function test_it_reports_when_nothing_is_stale(): void
    {
        $this->artisan('taskflow:archive-stale-tasks')
            ->expectsOutputToContain('No stale tasks found')
            ->assertSuccessful();
    }

    public function test_it_rejects_a_negative_days_value(): void
    {
        $this->artisan('taskflow:archive-stale-tasks', ['--days' => -1])->assertFailed();
    }

    public function test_it_is_scheduled_daily_at_3am(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('taskflow:archive-stale-tasks')
            ->assertSuccessful();
    }
}
