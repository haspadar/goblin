<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Output;

use Goblin\Output\PrefixedOutput;
use Goblin\Tests\Fake\FakeOutput;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PrefixedOutputTest extends TestCase
{
    #[Test]
    public function prependsPrefixToInfoMessage(): void
    {
        $fake = new FakeOutput();
        (new PrefixedOutput('Goblin: ', $fake))->info('loading issue');

        self::assertSame(
            'Goblin: loading issue',
            $fake->infos[0],
            'info message must have prefix',
        );
    }

    #[Test]
    public function prependsPrefixToSuccessMessage(): void
    {
        $fake = new FakeOutput();
        (new PrefixedOutput('Goblin: ', $fake))->success('Commit is valid');

        self::assertSame(
            'Goblin: Commit is valid',
            $fake->successes[0],
            'success message must have prefix',
        );
    }

    #[Test]
    public function prependsPrefixToErrorMessage(): void
    {
        $fake = new FakeOutput();
        (new PrefixedOutput('Goblin: ', $fake))->error('key mismatch');

        self::assertSame(
            'Goblin: key mismatch',
            $fake->errors[0],
            'error message must have prefix',
        );
    }

    #[Test]
    public function prependsPrefixToMutedMessage(): void
    {
        $fake = new FakeOutput();
        (new PrefixedOutput('Goblin: ', $fake))->muted('skipped');

        self::assertSame(
            'Goblin: skipped',
            $fake->muted[0],
            'muted message must have prefix',
        );
    }
}
