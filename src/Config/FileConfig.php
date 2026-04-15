<?php

declare(strict_types=1);

namespace Goblin\Config;

use Goblin\GoblinException;
use Override;

/**
 * Configuration backed by a parsed PHP array.
 */
final readonly class FileConfig implements Config
{
    /**
     * Wraps already-parsed configuration data.
     *
     * @param array<string, string|list<string>|array<string, mixed>> $data
     */
    public function __construct(private array $data) {}

    #[Override]
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }

    #[Override]
    public function value(string $name): string
    {
        if (!$this->has($name)) {
            throw new GoblinException("Missing config key: {$name}");
        }

        $value = $this->data[$name];

        if (is_array($value)) {
            throw new GoblinException("Config key \"{$name}\" is a list, use values()");
        }

        return $value;
    }

    #[Override]
    public function values(string $name): array
    {
        if (!$this->has($name)) {
            throw new GoblinException("Missing config key: {$name}");
        }

        $value = $this->data[$name];

        if (!is_array($value)) {
            throw new GoblinException("Config key \"{$name}\" is a scalar, use value()");
        }

        /** @psalm-var list<string> $value */
        return $value;
    }

    #[Override]
    public function map(string $name): array
    {
        if (!$this->has($name)) {
            throw new GoblinException("Missing config key: {$name}");
        }

        $value = $this->data[$name];

        if (!is_array($value)) {
            throw new GoblinException("Config key \"{$name}\" is not a map, use value()");
        }

        /** @psalm-var array<string, mixed> $value */
        return $value;
    }
}
