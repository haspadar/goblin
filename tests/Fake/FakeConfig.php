<?php

declare(strict_types=1);

namespace Goblin\Tests\Fake;

use Goblin\Config\Config;
use Goblin\GoblinException;

/**
 * In-memory configuration for tests
 */
final readonly class FakeConfig implements Config
{
    /**
     * @param array<string, string|list<string>> $data
     */
    public function __construct(
        private array $data,
    ) {
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }

    public function value(string $name): string
    {
        if (!$this->has($name)) {
            throw new GoblinException("Missing config key: {$name}");
        }

        $value = $this->data[$name];

        if (!is_string($value)) {
            throw new GoblinException("Config key \"{$name}\" is not a scalar string");
        }

        return $value;
    }

    /** @return list<string> */
    public function values(string $name): array
    {
        if (!$this->has($name)) {
            throw new GoblinException("Missing config key: {$name}");
        }

        $value = $this->data[$name];

        if (!is_array($value)) {
            throw new GoblinException("Config key \"{$name}\" is a scalar, use value()");
        }

        return array_values($value);
    }
}
