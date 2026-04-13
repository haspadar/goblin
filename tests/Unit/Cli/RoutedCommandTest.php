<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Cli;

use Goblin\Cli\Arguments;
use Goblin\Cli\Command\RoutedCommand;
use Goblin\GoblinException;
use Goblin\Tests\Fake\FakeCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RoutedCommandTest extends TestCase
{
    #[Test]
    public function dispatchesToMatchingCommand(): void
    {
        $fake = new FakeCommand(0);
        $router = new RoutedCommand(['greet' => $fake]);

        $code = $router->run(new Arguments('greet', [], []));

        self::assertSame(
            0,
            $code,
            'router must return exit code from matched command',
        );
    }

    #[Test]
    public function throwsForUnknownCommand(): void
    {
        $router = new RoutedCommand(['greet' => new FakeCommand(0)]);

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('Unknown command: deploy');

        $router->run(new Arguments('deploy', [], []));
    }

    #[Test]
    public function throwsForEmptyCommand(): void
    {
        $router = new RoutedCommand(['greet' => new FakeCommand(0)]);

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('Unknown command: ');

        $router->run(new Arguments('', [], []));
    }

    #[Test]
    public function passesArgumentsToCommand(): void
    {
        $fake = new FakeCommand(0);
        $router = new RoutedCommand(['issue' => $fake]);
        $args = new Arguments('issue', ['mode' => 'raw'], ['PROJ-42']);

        $router->run($args);

        self::assertSame(
            'PROJ-42',
            $fake->lastArgs()->positional(0),
            'router must pass original arguments to command',
        );
    }
}
