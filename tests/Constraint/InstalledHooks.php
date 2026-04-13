<?php

declare(strict_types=1);

namespace Goblin\Tests\Constraint;

use Goblin\Cli\InstallHook;
use Goblin\Tests\Fake\FakeOutput;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * Asserts that FakeOutput recorded installation of the given hooks.
 */
final class InstalledHooks extends Constraint
{
    /** @var list<string> */
    private readonly array $expected;

    public function __construct()
    {
        $this->expected = array_map(
            static fn(InstallHook $hook): string => "Installed {$hook->value}",
            InstallHook::cases(),
        );
    }

    public function toString(): string
    {
        return 'installed hooks ' . implode(', ', $this->expected);
    }

    protected function matches(mixed $other): bool
    {
        return $other instanceof FakeOutput
            && $other->successes === $this->expected;
    }
}
