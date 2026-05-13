<?php

declare(strict_types=1);

namespace Goblin\Git;

use Goblin\GoblinException;

/**
 * Resolves commit message from file path or raw text.
 */
final readonly class CommitMessage
{
    /**
     * Stores the raw input (file path or text).
     *
     * @param string $input Input value.
     */
    public function __construct(private string $input) {}

    /**
     * Returns commit message text.
     *
     * @throws GoblinException
     */
    public function text(): string
    {
        if ($this->input === '') {
            throw new GoblinException(
                'Commit message is required',
            );
        }

        if (is_file($this->input)) {
            $content = file_get_contents($this->input);

            if (!is_string($content)) {
                throw new GoblinException(
                    "Cannot read commit message file: {$this->input}",
                );
            }

            return trim($content);
        }

        return $this->input;
    }
}
