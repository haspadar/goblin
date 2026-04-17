<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Docker;

use Goblin\Docker\ComposeContainer;
use Goblin\GoblinException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ComposeContainerTest extends TestCase
{
    #[Test]
    public function returnsContainerNameOfRequestedService(): void
    {
        $container = (new ComposeContainer(__DIR__ . '/fixtures/valid', 'app'))->name();

        self::assertSame('fixtures-app', $container, 'must read services.app.container_name');
    }

    #[Test]
    public function returnsContainerNameOfAnotherService(): void
    {
        $container = (new ComposeContainer(__DIR__ . '/fixtures/valid', 'redis'))->name();

        self::assertSame('fixtures-redis', $container, 'service argument must select which block to read');
    }

    #[Test]
    public function throwsWhenComposeFileMissing(): void
    {
        $this->expectException(GoblinException::class);

        (new ComposeContainer(__DIR__ . '/fixtures/empty', 'app'))->name();
    }

    #[Test]
    public function throwsWhenServicesBlockMissing(): void
    {
        $this->expectException(GoblinException::class);

        (new ComposeContainer(__DIR__ . '/fixtures/no-services-block', 'app'))->name();
    }

    #[Test]
    public function throwsWhenRequestedServiceAbsent(): void
    {
        $this->expectException(GoblinException::class);

        (new ComposeContainer(__DIR__ . '/fixtures/no-service', 'app'))->name();
    }

    #[Test]
    public function throwsWhenContainerNameAbsent(): void
    {
        $this->expectException(GoblinException::class);

        (new ComposeContainer(__DIR__ . '/fixtures/no-container-name', 'app'))->name();
    }

    #[Test]
    public function ignoresContainerNameInsideNestedBlock(): void
    {
        $container = (new ComposeContainer(__DIR__ . '/fixtures/nested-container-name', 'app'))->name();

        self::assertSame('real-app', $container, 'must ignore container_name nested under environment');
    }

    #[Test]
    public function stripsInlineComment(): void
    {
        $container = (new ComposeContainer(__DIR__ . '/fixtures/inline-comment', 'app'))->name();

        self::assertSame('commented-app', $container, 'must drop trailing YAML comment from value');
    }

    #[Test]
    public function throwsWhenServiceBodyIsEmpty(): void
    {
        $this->expectException(GoblinException::class);

        (new ComposeContainer(__DIR__ . '/fixtures/empty-service-body', 'app'))->name();
    }

    #[Test]
    public function throwsWhenRequestedServiceAppearsOnlyOutsideServicesBlock(): void
    {
        $this->expectException(GoblinException::class);

        (new ComposeContainer(__DIR__ . '/fixtures/container-name-outside-services', 'app'))->name();
    }

    #[Test]
    public function stripsSurroundingQuotesFromValue(): void
    {
        $container = (new ComposeContainer(__DIR__ . '/fixtures/quoted-value', 'app'))->name();

        self::assertSame('quoted-app', $container, 'must strip surrounding double quotes');
    }

    #[Test]
    public function readsAlternativeComposeFilename(): void
    {
        $container = (new ComposeContainer(__DIR__ . '/fixtures/alt-filename', 'app'))->name();

        self::assertSame('alt-compose-app', $container, 'must accept compose.yml alongside docker-compose.yml');
    }
}
