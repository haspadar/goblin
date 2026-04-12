<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\MergeRequest;

use Goblin\MergeRequest\DraftTitle;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DraftTitleTest extends TestCase
{
    #[Test]
    public function addsDraftPrefix(): void
    {
        self::assertSame(
            'Draft: Fix login redirect',
            (new DraftTitle('Fix login redirect'))->drafted(),
            'plain title must get Draft: prefix',
        );
    }

    #[Test]
    public function preservesExistingDraftPrefix(): void
    {
        self::assertSame(
            'Draft: Update cache layer',
            (new DraftTitle('Draft: Update cache layer'))->drafted(),
            'title with Draft: must not be double-prefixed',
        );
    }

    #[Test]
    public function preservesExistingWipPrefix(): void
    {
        self::assertSame(
            'WIP: Refactor queue',
            (new DraftTitle('WIP: Refactor queue'))->drafted(),
            'title with WIP: must not be double-prefixed',
        );
    }

    #[Test]
    public function removesDraftPrefix(): void
    {
        self::assertSame(
            'Add retry logic',
            (new DraftTitle('Draft: Add retry logic'))->ready(),
            'Draft: prefix must be stripped',
        );
    }

    #[Test]
    public function removesWipPrefix(): void
    {
        self::assertSame(
            'Migrate schema',
            (new DraftTitle('WIP: Migrate schema'))->ready(),
            'WIP: prefix must be stripped',
        );
    }
}
