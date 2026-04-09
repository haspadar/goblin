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
    public function returnsFullArrayFromValidFile(): void
    {
        $file = new ConfigFile(__DIR__ . '/fixtures/valid.php');

        self::assertSame(
            [
                'jira-url' => 'https://acme.atlassian.net',
                'jira-user' => 'tester@example.com',
                'jira-token' => 'test-token-abc',
                'project-regex' => '/^([A-Z]+)-\d+/',
                'protected-branches' => ['dev', 'stage', 'master'],
            ],
            $file->data(),
            'valid config file must return exact parsed array',
        );
    }

    #[Test]
    public function throwsWhenFileNotFound(): void
    {
        $file = new ConfigFile(sys_get_temp_dir() . '/goblin-no-such-config.php');

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

    #[Test]
    public function throwsWhenPathIsDirectory(): void
    {
        $file = new ConfigFile(__DIR__ . '/fixtures');

        $this->expectException(GoblinException::class);
        $file->data();
    }
}
