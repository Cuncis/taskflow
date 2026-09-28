<?php

namespace App\Domain\Task\Export;

interface TaskExporterInterface
{
    public function export(array $tasks): string;

    public function canExport(array $tasks): bool;
}
