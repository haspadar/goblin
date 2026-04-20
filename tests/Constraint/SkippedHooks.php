<?php

declare(strict_types=1);

namespace Goblin\Tests\Constraint;

use Goblin\Cli\InstallHook;
use Goblin\Tests\Fake\FakeOutput;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * Asserts that FakeOutput recorded skipping of the given hooks.
 */
final class SkippedHooks extends Constraint
{
    /** @var list<string> */
    private readonly array $expected;

    public function __construct()
    {
        $this->expected = array_map(
            static fn(InstallHook $hook): string => "Skipped {$hook->value} (already installed)",
            InstallHook::cases(),
        );
    }

    public function toString(): string
    {
        return 'skipped hooks ' . implode(', ', $this->expected);
    }

    protected function matches(mixed $other): bool
    {
        return $other instanceof FakeOutput
            && $other->muted === $this->expected;
    }
}
