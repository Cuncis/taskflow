<?php

namespace App\Domain\Task\Export;

use Override;
use RuntimeException;
use SimpleXMLElement;

class XmlTaskExporter implements TaskExporterInterface
{
    #[Override]
    public function export(array $tasks): string
    {
        if (! $this->canExport($tasks)) {
            throw new RuntimeException('XML export not supported for these tasks.');
        }

        $root = new SimpleXMLElement('<tasks/>');

        foreach ($tasks as $task) {
            $node = $root->addChild('task');

            foreach ($task as $key => $value) {
                $node->addChild($key, htmlspecialchars((string) $value));
            }
        }

        return $root->asXML();
    }

    /**
     * XML has no task-count limit, so this is always true.
     */
    #[Override]
    public function canExport(array $tasks): bool
    {
        return true;
    }
}
