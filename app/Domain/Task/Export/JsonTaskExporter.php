<?php

namespace App\Domain\Task\Export;

use Override;

class JsonTaskExporter implements TaskExporterInterface
{
    #[Override]
    public function export(array $tasks): string
    {
        return json_encode($tasks);
    }
}
