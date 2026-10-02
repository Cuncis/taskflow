<?php

namespace App\Domain\Task\Console;

use App\Domain\Task\Actions\ArchiveTaskAction;
use App\Domain\Task\Models\Task;
use App\Domain\Task\TaskStatus;
use Illuminate\Console\Command;

class ArchiveStaleTasks extends Command
{
    protected $signature = 'taskflow:archive-stale-tasks
        {--days=90 : Number of days a done task must be untouched before archiving}
        {--dry-run : Show what would be archived without actually archiving anything}';

    protected $description = 'Archive tasks that have been done and untouched for a given number of days';

    public function handle(ArchiveTaskAction $archiveTask): int
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');

        if ($days < 0) {
            $this->error('--days must be zero or greater.');

            return self::FAILURE;
        }

        $staleTasks = Task::where('status', TaskStatus::Done)
            ->whereNull('archived_at')
            ->where('updated_at', '<', now()->subDays($days))
            ->get();

        if ($staleTasks->isEmpty()) {
            $this->info("No stale tasks found (done and untouched for {$days}+ days).");

            return self::SUCCESS;
        }

        $this->info("Found {$staleTasks->count()} stale task(s).");

        if ($dryRun) {
            foreach ($staleTasks as $task) {
                $this->line("[DRY RUN] Would archive: {$task->title} (last updated {$task->updated_at->diffForHumans()})");
            }

            return self::SUCCESS;
        }

        // Build the summary before archiving: archiving touches updated_at.
        $rows = $staleTasks->map(fn (Task $task) => [
            $task->title,
            $task->updated_at->diffForHumans(),
            $task->status->value,
        ])->all();

        $this->withProgressBar($staleTasks, function (Task $task) use ($archiveTask) {
            $archiveTask($task);
        });

        $this->newLine(2);
        $this->table(['Title', 'Last Updated', 'Status'], $rows);
        $this->info('Done.');

        return self::SUCCESS;
    }
}
