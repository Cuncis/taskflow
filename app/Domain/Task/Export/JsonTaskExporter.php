<?php

namespace App\Domain\Task\Export;

use Override;
use RuntimeException;

class JsonTaskExporter implements TaskExporterInterface
{
    #[Override]
    public function export(array $tasks): string
    {
        if (! $this->canExport($tasks)) {
            throw new RuntimeException('JSON export not supported for these tasks.');
        }

        return json_encode($tasks);
    }

    /**
     * JSON has no task-count limit, so this is always true.
     */
    #[Override]
    public function canExport(array $tasks): bool
    {
        return true;
    }
}
