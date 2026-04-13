<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Git;

use Goblin\Git\CommitMessage;
use Goblin\GoblinException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CommitMessageTest extends TestCase
{
    #[Test]
    public function returnsTextWhenInputIsPlainString(): void
    {
        self::assertSame(
            'PROJ-42 Fix login timeout',
            (new CommitMessage('PROJ-42 Fix login timeout'))->text(),
            'plain string input must be returned as-is',
        );
    }

    #[Test]
    public function readsTextFromFileWhenInputIsFilePath(): void
    {
        $file = $this->commitFile("PROJ-99 Add caching layer\n");

        self::assertSame(
            'PROJ-99 Add caching layer',
            (new CommitMessage($file))->text(),
            'file path input must return trimmed file contents',
        );
    }

    #[Test]
    public function throwsWhenInputIsEmpty(): void
    {
        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('Commit message is required');

        (new CommitMessage(''))->text();
    }

    private function commitFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'goblin-commit-');
        assert(is_string($path));
        file_put_contents($path, $content);

        return $path;
    }
}
