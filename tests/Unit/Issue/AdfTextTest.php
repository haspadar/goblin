<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Issue;

use Goblin\Issue\AdfText;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AdfTextTest extends TestCase
{
    #[Test]
    public function returnsPlainTextFromParagraph(): void
    {
        $adf = new AdfText([
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'Hello world'],
                    ],
                ],
            ],
        ]);

        self::assertSame(
            "Hello world\n\n",
            $adf->text(),
            'paragraph must end with double newline',
        );
    }

    #[Test]
    public function rendersHardBreakAsNewline(): void
    {
        $adf = new AdfText([
            'type' => 'paragraph',
            'content' => [
                ['type' => 'text', 'text' => 'first'],
                ['type' => 'hardBreak'],
                ['type' => 'text', 'text' => 'second'],
            ],
        ]);

        self::assertSame(
            "first\nsecond\n\n",
            $adf->text(),
            'hardBreak must produce a newline between text nodes',
        );
    }

    #[Test]
    public function rendersMentionAsText(): void
    {
        $adf = new AdfText([
            'type' => 'mention',
            'attrs' => ['id' => 'u-901', 'text' => '@dmitry'],
        ]);

        self::assertSame(
            '@dmitry',
            $adf->text(),
            'mention must render as its text attribute',
        );
    }

    #[Test]
    public function rendersInlineCardAsUrl(): void
    {
        $adf = new AdfText([
            'type' => 'inlineCard',
            'attrs' => ['data' => ['status' => 'resolved'], 'url' => 'https://jira.example.com/browse/FX-10'],
        ]);

        self::assertSame(
            'https://jira.example.com/browse/FX-10',
            $adf->text(),
            'inlineCard must render as its url attribute',
        );
    }

    #[Test]
    public function rendersBulletListWithDashes(): void
    {
        $adf = new AdfText([
            'type' => 'bulletList',
            'content' => [
                [
                    'type' => 'listItem',
                    'content' => [
                        [
                            'type' => 'paragraph',
                            'content' => [
                                ['type' => 'text', 'text' => 'Alpha'],
                            ],
                        ],
                    ],
                ],
                [
                    'type' => 'listItem',
                    'content' => [
                        [
                            'type' => 'paragraph',
                            'content' => [
                                ['type' => 'text', 'text' => 'Beta'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertSame(
            "- Alpha\n- Beta\n",
            $adf->text(),
            'bullet list items must start with dash',
        );
    }

    #[Test]
    public function rendersOrderedListWithDashes(): void
    {
        $adf = new AdfText([
            'type' => 'orderedList',
            'attrs' => ['order' => 1],
            'content' => [
                [
                    'type' => 'listItem',
                    'content' => [
                        [
                            'type' => 'paragraph',
                            'content' => [
                                ['type' => 'text', 'text' => 'Step one'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertSame(
            "- Step one\n",
            $adf->text(),
            'ordered list items render as dashes in plaintext',
        );
    }

    #[Test]
    public function returnsEmptyStringForEmptyNode(): void
    {
        $adf = new AdfText([]);

        self::assertSame(
            '',
            $adf->text(),
            'empty node must produce empty string',
        );
    }

    #[Test]
    public function rendersEmbedCardAsUrl(): void
    {
        $adf = new AdfText([
            'type' => 'embedCard',
            'attrs' => ['layout' => 'wide', 'url' => 'https://confluence.example.com/page/42'],
        ]);

        self::assertSame(
            'https://confluence.example.com/page/42',
            $adf->text(),
            'embedCard must render as its url attribute',
        );
    }

    #[Test]
    public function rendersEmptyWhenAttrsIsNotArray(): void
    {
        $adf = new AdfText([
            'type' => 'mention',
            'attrs' => 'invalid',
        ]);

        self::assertSame(
            '',
            $adf->text(),
            'non-array attrs must produce empty string',
        );
    }

    #[Test]
    public function rendersHeadingWithDoubleNewline(): void
    {
        $adf = new AdfText([
            'type' => 'heading',
            'content' => [
                ['type' => 'text', 'text' => 'Summary'],
            ],
        ]);

        self::assertSame(
            "Summary\n\n",
            $adf->text(),
            'heading must end with double newline',
        );
    }
}
