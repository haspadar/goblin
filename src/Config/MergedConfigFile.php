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
     * @param string $base Base configuration path.
     * @param string $overlay Overlay configuration path.
     */
    public function __construct(private string $base, private string $overlay = '') {}

    /**
     * Returns base data merged with overlay when the overlay exists and differs from base.
     *
     * @throws GoblinException
     * @return array<string, string|list<string>>
     */
    public function data(): array
    {
        $loaded = (new ConfigFile($this->base))->data();

        if ($this->overlay === '' || !is_file($this->overlay)) {
            return $loaded;
        }

        if (realpath($this->overlay) === realpath($this->base)) {
            return $loaded;
        }

        return array_replace($loaded, (new ConfigFile($this->overlay))->data());
    }
}
