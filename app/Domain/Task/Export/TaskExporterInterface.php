<?php

namespace App\Domain\Task\Export;

interface TaskExporterInterface
{
    /**
     * @param  array<int, array<string, mixed>>  $tasks
     */
    public function export(array $tasks): string;

    /**
     * @param  array<int, array<string, mixed>>  $tasks
     */
    public function canExport(array $tasks): bool;
}
