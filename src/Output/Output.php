<?php

declare(strict_types=1);

namespace Goblin\Output;

/**
 * Writes user-facing messages to an output channel.
 *
 * @psalm-api
 */
interface Output
{
    /**
     * Emits an informational message.
     *
     * @param string $text Text value.
     */
    public function info(string $text): void;

    /**
     * Emits a success message.
     *
     * @param string $text Text value.
     */
    public function success(string $text): void;

    /**
     * Emits an error message.
     *
     * @param string $text Text value.
     */
    public function error(string $text): void;

    /**
     * Emits a muted (secondary) message.
     *
     * @param string $text Text value.
     */
    public function muted(string $text): void;
}
