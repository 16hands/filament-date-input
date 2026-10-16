<?php

namespace SixteenHands\FilamentDateInput\Assets;

/**
 * Filament tags asset URLs with the owning Composer package's version. This
 * package is autoloaded from the repository rather than installed, so Filament
 * falls back to its own version and browsers keep a stale copy across deploys.
 * Hashing the file contents instead changes the tag whenever the file does.
 */
trait VersionsByContent
{
    public function getVersion(): string
    {
        $sourcePath = $this->getPath();

        if ($sourcePath !== null && is_file($sourcePath)) {
            return substr(hash_file('sha256', $sourcePath), 0, 16);
        }

        return parent::getVersion();
    }
}
