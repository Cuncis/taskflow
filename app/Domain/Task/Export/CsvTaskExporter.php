<?php

namespace App\Domain\Task\Export;

class CsvTaskExporter implements TaskExporterInterface
{
    public function export(array $tasks): string
    {
        $lines = ['title, status'];

        foreach ($tasks as $task) {
            $lines[] = "{$task['title']},{$task['status']}";
        }

        return implode("\n", $lines);
    }
}
