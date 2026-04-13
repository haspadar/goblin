<?php

declare(strict_types=1);

namespace Goblin\Tests\Fixture;

use PHPUnit\Framework\TestCase;

/**
 * Skips the test when Docker CLI is not available.
 */
final class WithDocker
{
    /**
     * Checks Docker availability, then runs the callback.
     *
     * @template T
     * @param \Closure(): T $callback
     * @return T
     */
    public function run(\Closure $callback): mixed
    {
        exec('docker info 2>/dev/null', $lines, $code);

        if ($code !== 0) {
            TestCase::markTestSkipped('Docker CLI is not available');
        }

        return $callback();
    }
}
