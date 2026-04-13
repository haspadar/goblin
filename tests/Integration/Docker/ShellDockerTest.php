<?php

declare(strict_types=1);

namespace Goblin\Tests\Integration\Docker;

use Goblin\Docker\ShellDocker;
use Goblin\Tests\Fake\FakeOutput;
use Goblin\Tests\Fixture\WithDocker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ShellDockerTest extends TestCase
{
    #[Test]
    public function returnsFalseForNonexistentContainer(): void
    {
        (new WithDocker())->run(function (): void {
            self::assertFalse(
                (new ShellDocker(new FakeOutput()))->isRunning('goblin-nonexistent-' . bin2hex(random_bytes(4))),
                'must return false for a container that does not exist',
            );
        });
    }

    #[Test]
    public function execReturnsNonZeroForMissingContainer(): void
    {
        (new WithDocker())->run(function (): void {
            $exitCode = (new ShellDocker(new FakeOutput()))->exec(
                'goblin-nonexistent-' . bin2hex(random_bytes(4)),
                'true',
            );

            self::assertNotSame(
                0,
                $exitCode,
                'must return non-zero exit code for a missing container',
            );
        });
    }
}
