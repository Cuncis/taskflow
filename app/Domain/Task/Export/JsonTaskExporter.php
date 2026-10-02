<?php

namespace App\Domain\Task\Export;

use Override;
use RuntimeException;

class JsonTaskExporter implements TaskExporterInterface
{
    /**
     * @param array<int, array<string, scalar|null>> $tasks
     */
    #[Override]
    public function export(array $tasks): string
    {
        if (! $this->canExport($tasks)) {
            throw new RuntimeException('JSON export not supported for these tasks.');
        }

        return json_encode($tasks, JSON_THROW_ON_ERROR);
    }

    /**
     * JSON has no task-count limit, so this is always true.
     *
     * @param array<int, array<string, scalar|null>> $tasks
     */
    #[Override]
    public function canExport(array $tasks): bool
    {
        return true;
    }
}
