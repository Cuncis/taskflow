<?php

namespace App\Domain\Task\Export;

class MultiFormatTaskExporter
{
    /**
     * @param array<int, array<string, scalar|null>> $tasks
     * @param array<int, TaskExporterInterface> $exporters
     * @return array<int, string>
     */
    public function exportAllFormats(array $tasks, array $exporters): array
    {
        $results = [];

        foreach ($exporters as $exporter) {
            if ($exporter->canExport($tasks)) {
                $results[] = $exporter->export($tasks);
            } else {
                $results[] = 'Skipped — task count exceeds this format\'s limit.';
            }
        }

        return $results;
    }
}
