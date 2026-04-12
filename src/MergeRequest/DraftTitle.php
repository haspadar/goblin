<?php

declare(strict_types=1);

namespace Goblin\MergeRequest;

/**
 * Manages Draft: prefix on merge request titles.
 *
 * @psalm-api
 */
final readonly class DraftTitle
{
    /**
     * Stores the original title.
     */
    public function __construct(private string $title) {}

    /**
     * Returns title with Draft: prefix added.
     */
    public function drafted(): string
    {
        if (preg_match('/^(Draft:|WIP:)\s*/i', $this->title) === 1) {
            return $this->title;
        }

        return 'Draft: ' . $this->title;
    }

    /**
     * Returns title with Draft:/WIP: prefix removed.
     */
    public function ready(): string
    {
        $cleaned = preg_replace('/^(Draft:|WIP:)\s*/i', '', $this->title);

        return $cleaned ?? $this->title;
    }
}
