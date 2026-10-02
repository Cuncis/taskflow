<?php

namespace App\Domain\Task\Export;

interface TaskExporterInterface
{
    /**
     * @param array<int, array<string, scalar|null>> $tasks
     */
    public function export(array $tasks): string;

    /**
     * @param array<int, array<string, scalar|null>> $tasks
     */
    public function canExport(array $tasks): bool;
}
