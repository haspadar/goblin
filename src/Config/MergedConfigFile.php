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
     */
    public function __construct(private string $basePath, private ?string $overlayPath = null) {}

    /**
     * Returns base data merged with overlay when the overlay exists and differs from base.
     *
     * @throws GoblinException
     * @return array<string, string|list<string>>
     */
    public function data(): array
    {
        $base = (new ConfigFile($this->basePath))->data();

        if ($this->overlayPath === null || !is_file($this->overlayPath)) {
            return $base;
        }

        if (realpath($this->overlayPath) === realpath($this->basePath)) {
            return $base;
        }

        return array_replace($base, (new ConfigFile($this->overlayPath))->data());
    }
}
