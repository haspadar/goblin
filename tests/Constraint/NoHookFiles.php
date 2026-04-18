<?php

declare(strict_types=1);

namespace Goblin\Tests\Constraint;

use Goblin\Cli\InstallHook;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * Asserts that no goblin-managed hook files exist in the given directory.
 */
final class NoHookFiles extends Constraint
{
    public function toString(): string
    {
        return 'no goblin hook files in the directory';
    }

    protected function matches(mixed $other): bool
    {
        if (!is_string($other) || !is_dir($other)) {
            return false;
        }

        foreach (InstallHook::cases() as $hook) {
            if (file_exists($other . '/' . $hook->value)) {
                return false;
            }
        }

        return true;
    }
}
