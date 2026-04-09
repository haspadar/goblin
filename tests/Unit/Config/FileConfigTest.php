<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Config;

use Goblin\Config\FileConfig;
use Goblin\GoblinException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FileConfigTest extends TestCase
{
    #[Test]
    public function returnsScalarValue(): void
    {
        $config = new FileConfig([
            'jira-url' => 'https://acme.atlassian.net',
        ]);

        self::assertSame(
            'https://acme.atlassian.net',
            $config->value('jira-url'),
            'scalar key must return its string value',
        );
    }

    #[Test]
    public function returnsListValues(): void
    {
        $config = new FileConfig([
            'protected-branches' => ['dev', 'stage', 'master'],
        ]);

        self::assertSame(
            ['dev', 'stage', 'master'],
            $config->values('protected-branches'),
            'list key must return array of strings',
        );
    }

    #[Test]
    public function reportsMissingKey(): void
    {
        $config = new FileConfig([]);

        self::assertFalse(
            $config->has('nonexistent'),
            'has() must return false for missing key',
        );
    }

    #[Test]
    public function throwsWhenKeyIsMissing(): void
    {
        $config = new FileConfig([]);

        $this->expectException(GoblinException::class);
        $config->value('nonexistent');
    }

    #[Test]
    public function throwsWhenScalarAccessedAsList(): void
    {
        $config = new FileConfig([
            'jira-url' => 'https://acme.atlassian.net',
        ]);

        $this->expectException(GoblinException::class);
        $config->values('jira-url');
    }

    #[Test]
    public function throwsWhenListAccessedAsScalar(): void
    {
        $config = new FileConfig([
            'protected-branches' => ['dev', 'stage', 'master'],
        ]);

        $this->expectException(GoblinException::class);
        $config->value('protected-branches');
    }
}
