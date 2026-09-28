<?php

namespace App\Domain\Storage;

interface FileStorage
{
    public function save(string $path, string $contents): void;
}
