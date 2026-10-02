<?php

namespace App\Domain\Task\Export;

use Override;
use RuntimeException;

class CsvTaskExporter implements TaskExporterInterface
{
    /**
     * @param  array<int, array<string, mixed>>  $tasks
     */
    #[Override]
    public function export(array $tasks): string
    {
        if (! $this->canExport($tasks)) {
            throw new RuntimeException('CSV export not supported for these tasks.');
        }

        $lines = ['title, status'];

        foreach ($tasks as $task) {
            $lines[] = "{$task['title']},{$task['status']}";
        }

        return implode("\n", $lines);
    }

    /**
     * CSV has no task-count limit, so this is always true.
     *
     * @param  array<int, array<string, mixed>>  $tasks
     */
    #[Override]
    public function canExport(array $tasks): bool
    {
        return true;
    }
}
