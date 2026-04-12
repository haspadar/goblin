<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Docker;

use Goblin\Docker\ShellDocker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ShellDockerTest extends TestCase
{
    #[Test]
    public function returnsFalseForNonexistentContainer(): void
    {
        exec('docker info 2>/dev/null', $lines, $code);

        if ($code !== 0) {
            self::markTestSkipped('Docker CLI is not available');
        }

        self::assertFalse(
            (new ShellDocker())->isRunning('goblin-nonexistent-' . bin2hex(random_bytes(4))),
            'must return false for a container that does not exist',
        );
    }

    #[Test]
    public function execReturnsNonZeroForMissingContainer(): void
    {
        exec('docker info 2>/dev/null', $lines, $code);

        if ($code !== 0) {
            self::markTestSkipped('Docker CLI is not available');
        }

        $code = (new ShellDocker())->exec(
            'goblin-nonexistent-' . bin2hex(random_bytes(4)),
            'true',
        );

        self::assertNotSame(
            0,
            $code,
            'must return non-zero exit code for a missing container',
        );
    }
}
