<?php

namespace App\Domain\Task\Export;

use Override;
use SimpleXMLElement;

class XmlTaskExporter implements TaskExporterInterface
{
    #[Override]
    public function export(array $tasks): string
    {
        $root = new SimpleXMLElement('<tasks/>');

        foreach ($tasks as $task) {
            $node = $root->addChild('task');

            foreach ($task as $key => $value) {
                $node->addChild($key, htmlspecialchars((string) $value));
            }
        }

        return $root->asXML();
    }
}
