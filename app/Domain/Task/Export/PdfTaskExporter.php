<?php

namespace App\Domain\Task\Export;

class PdfTaskExporter implements TaskExporterInterface
{
    private const MAX_TASKS = 100;

    public function canExport(array $tasks): bool
    {
        return count($tasks) <= self::MAX_TASKS;
    }

    public function export(array $tasks): string
    {
        if (! $this->canExport($tasks)) {
            throw new \RuntimeException('PDF export not supported for more than '.self::MAX_TASKS.' tasks.');
        }

        // Real PDF generation would go here — we'll do this for real in Phase 5's PDF work.
        return "PDF content for {$this->countLabel($tasks)}";
    }

    private function countLabel(array $tasks): string
    {
        return count($tasks).' tasks';
    }
}
