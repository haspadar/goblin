<?php

declare(strict_types=1);

namespace Goblin\Tests\Fake;

use Goblin\Output\Output;

/**
 * Captures output messages for assertions
 */
final class FakeOutput implements Output
{
    /** @var list<string> */
    public array $infos = [];

    /** @var list<string> */
    public array $successes = [];

    /** @var list<string> */
    public array $errors = [];

    /** @var list<string> */
    public array $muted = [];

    public function info(string $text): void
    {
        $this->infos[] = $text;
    }

    public function success(string $text): void
    {
        $this->successes[] = $text;
    }

    public function error(string $text): void
    {
        $this->errors[] = $text;
    }

    public function muted(string $text): void
    {
        $this->muted[] = $text;
    }
}
