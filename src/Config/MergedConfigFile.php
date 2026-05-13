<?php

declare(strict_types=1);

namespace Goblin\Config;

use Goblin\GoblinException;

/**
 * Base configuration overlaid with optional local keys.
 */
final readonly class MergedConfigFile
{
    /**
     * Stores the required base file and an optional overlay path.
     *
     * @param string $basePath Base configuration path.
     * @param string $overlayPath Overlay configuration path.
     */
    public function __construct(private string $basePath, private string $overlayPath = '') {}

    /**
     * Returns base data merged with overlay when the overlay exists and differs from base.
     *
     * @throws GoblinException
     * @return array<string, string|list<string>>
     */
    public function data(): array
    {
        $loaded = (new ConfigFile($this->basePath))->data();

        if ($this->overlayPath === '' || !is_file($this->overlayPath)) {
            return $loaded;
        }

        if (realpath($this->overlayPath) === realpath($this->basePath)) {
            return $loaded;
        }

        return array_replace($loaded, (new ConfigFile($this->overlayPath))->data());
    }
}
