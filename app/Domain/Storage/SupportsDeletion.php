<?php

namespace App\Domain\Storage;

interface SupportsDeletion
{
    public function delete(string $path): void;
}
