<?php

namespace App\Domain\Storage;

/**
 * Write-once storage: it can save, but it never claims it can delete.
 */
class ArchiveStorage implements FileStorage
{
    /** @var array<string, string> */
    public array $files = [];

    public function save(string $path, string $contents): void
    {
        $this->files[$path] = $contents;
    }
}
