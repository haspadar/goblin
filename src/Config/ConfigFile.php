<?php

declare(strict_types=1);

namespace Goblin\Config;

use Goblin\GoblinException;

/**
 * Reads a PHP config file and returns its array.
 */
final readonly class ConfigFile
{
    /**
     * Stores the path to the config file.
     */
    public function __construct(private string $path) {}

    /**
     * Parses the PHP file and returns configuration data.
     *
     * @throws GoblinException
     * @return array<string, string|list<string>>
     */
    public function data(): array
    {
        if (!file_exists($this->path)) {
            throw new GoblinException("Config file not found: {$this->path}");
        }

        $data = require $this->path;

        if (!is_array($data)) {
            throw new GoblinException("Config file must return an array: {$this->path}");
        }

        /** @phpstan-var array<string, string|list<string>> $data */
        return $data;
    }
}
