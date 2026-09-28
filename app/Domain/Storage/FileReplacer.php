<?php

namespace App\Domain\Storage;

class FileReplacer
{
    /**
     * Needs BOTH abilities, so the type says so up front.
     */
    public function replace(FileStorage&SupportsDeletion $storage, string $old, string $new, string $contents): void
    {
        $storage->save($new, $contents);
        $storage->delete($old);
    }

    /**
     * Callers holding a plain FileStorage can still ask honestly.
     */
    public function replaceIfPossible(FileStorage $storage, string $old, string $new, string $contents): bool
    {
        if (! $storage instanceof SupportsDeletion) {
            return false; // nothing touched, so no half-done state
        }

        $this->replace($storage, $old, $new, $contents);

        return true;
    }
}
