<?php

declare(strict_types=1);

namespace Goblin\Tests\Integration\Shell;

use Goblin\Shell\LocalShell;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LocalShellTest extends TestCase
{
    #[Test]
    public function returnsZeroForSuccessfulCommand(): void
    {
        self::assertSame(
            0,
            (new LocalShell())->run('true'),
            'must return 0 when the host command exits successfully',
        );
    }

    #[Test]
    public function returnsNonZeroForFailingCommand(): void
    {
        self::assertNotSame(
            0,
            (new LocalShell())->run('false'),
            'must return non-zero when the host command exits with failure',
        );
    }
}
