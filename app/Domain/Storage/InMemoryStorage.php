<?php

namespace App\Domain\Storage;

class InMemoryStorage implements FileStorage, SupportsDeletion
{
    /** @var array<string, string> */
    public array $files = [];

    public function save(string $path, string $contents): void
    {
        $this->files[$path] = $contents;
    }

    public function delete(string $path): void
    {
        unset($this->files[$path]);
    }
}
