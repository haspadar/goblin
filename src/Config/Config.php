<?php

declare(strict_types=1);

namespace Goblin\Config;

use Goblin\GoblinException;

/**
 * Read-only access to configuration keys.
 *
 * @psalm-api
 */
interface Config
{
    /**
     * Checks whether a key exists in configuration.
     *
     * @throws GoblinException
     */
    public function has(string $name): bool;

    /**
     * Returns a single scalar value by key.
     *
     * @throws GoblinException
     */
    public function value(string $name): string;

    /**
     * Returns a list of strings by key.
     *
     * @throws GoblinException
     * @return list<string>
     */
    public function values(string $name): array;
}
