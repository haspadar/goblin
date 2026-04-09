<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Config;

use Goblin\Config\ConfigFile;
use Goblin\GoblinException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigFileTest extends TestCase
{
    #[Test]
    public function returnsArrayFromValidFile(): void
    {
        $file = new ConfigFile(__DIR__ . '/fixtures/valid.php');

        self::assertSame(
            'https://acme.atlassian.net',
            $file->data()['jira-url'],
            'valid config file must return parsed array',
        );
    }

    #[Test]
    public function throwsWhenFileNotFound(): void
    {
        $file = new ConfigFile('/tmp/goblin-no-such-config.php');

        $this->expectException(GoblinException::class);
        $file->data();
    }

    #[Test]
    public function throwsWhenFileReturnsNonArray(): void
    {
        $file = new ConfigFile(__DIR__ . '/fixtures/non-array.php');

        $this->expectException(GoblinException::class);
        $file->data();
    }
}
