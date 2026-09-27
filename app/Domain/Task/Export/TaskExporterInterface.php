<?php

namespace App\Domain\Task\Export;

interface TaskExporterInterface
{
    public function export(array $tasks): string;
}
