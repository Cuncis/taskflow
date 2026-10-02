<?php

namespace App\Domain\Task\Export;

use Override;
use RuntimeException;

class CsvTaskExporter implements TaskExporterInterface
{
    /**
     * @param array<int, array<string, scalar|null>> $tasks
     */
    #[Override]
    public function export(array $tasks): string
    {
        if (! $this->canExport($tasks)) {
            throw new RuntimeException('CSV export not supported for these tasks.');
        }

        // fputcsv quotes titles containing commas, quotes or newlines instead of corrupting the row.
        $handle = fopen('php://temp', 'r+') ?: throw new RuntimeException('Could not open a temporary stream for CSV export.');
        fputcsv($handle, ['title', 'status'], escape: '');

        foreach ($tasks as $task) {
            fputcsv($handle, [$task['title'], $task['status']], escape: '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return rtrim((string) $csv, "\n");
    }

    /**
     * CSV has no task-count limit, so this is always true.
     *
     * @param array<int, array<string, scalar|null>> $tasks
     */
    #[Override]
    public function canExport(array $tasks): bool
    {
        return true;
    }
}
