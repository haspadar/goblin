<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Issue;

use Goblin\GoblinException;
use Goblin\Issue\ProjectKey;
use Goblin\Tests\Fake\FakeConfig;
use Goblin\Tests\Fake\FakeGit;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProjectKeyTest extends TestCase
{
    #[Test]
    public function returnsExplicitArgument(): void
    {
        self::assertSame(
            'BEAM',
            (new ProjectKey('BEAM', new FakeGit('main'), $this->config()))->value(),
            'explicit argument must be returned as-is',
        );
    }

    #[Test]
    public function uppercasesExplicitArgument(): void
    {
        self::assertSame(
            'CORE',
            (new ProjectKey('core', new FakeGit('main'), $this->config()))->value(),
            'explicit argument must be uppercased',
        );
    }

    #[Test]
    public function trimsAndUppercasesExplicitArgument(): void
    {
        self::assertSame(
            'CORE',
            (new ProjectKey('  core  ', new FakeGit('main'), $this->config()))->value(),
            'explicit argument must be trimmed and uppercased',
        );
    }

    #[Test]
    public function extractsProjectFromBranch(): void
    {
        self::assertSame(
            'PLAT',
            (new ProjectKey('', new FakeGit('PLAT-512-new-login'), $this->config()))->value(),
            'project must be extracted from branch via regex',
        );
    }

    #[Test]
    public function throwsWhenBranchHasNoProject(): void
    {
        $this->expectException(GoblinException::class);

        (new ProjectKey('', new FakeGit('feature/no-project'), $this->config()))->value();
    }

    #[Test]
    public function throwsWhenRegexIsInvalid(): void
    {
        $this->expectException(GoblinException::class);

        $config = new FakeConfig(['project-regex' => '/[invalid']);

        (new ProjectKey('', new FakeGit('PLAT-100-task'), $config))->value();
    }

    #[Test]
    public function prefersArgumentOverBranch(): void
    {
        self::assertSame(
            'OPS',
            (new ProjectKey('OPS', new FakeGit('PLAT-200-deploy'), $this->config()))->value(),
            'explicit argument must take priority over branch detection',
        );
    }

    private function config(): FakeConfig
    {
        return new FakeConfig(['project-regex' => '/^([A-Z]+)-\d+/']);
    }
}
