<?php

declare(strict_types=1);

namespace Goblin\Daily;

/**
 * Translates days-ago offset to Russian day name.
 *
 * @psalm-api
 */
final readonly class DayLabel
{
    /**
     * Stores the number of days ago.
     *
     * @param int $daysAgo Days offset from today.
     */
    public function __construct(private int $daysAgo) {}

    /**
     * Returns human-readable day label in Russian.
     */
    public function text(): string
    {
        if ($this->daysAgo === 1) {
            return 'Вчера';
        }

        $timestamp = strtotime("-{$this->daysAgo} days");

        if (!is_int($timestamp)) {
            return "day-{$this->daysAgo}";
        }

        $weekday = date('N', $timestamp);

        return match ($weekday) {
            '1' => 'В понедельник',
            '2' => 'Во вторник',
            '3' => 'В среду',
            '4' => 'В четверг',
            '5' => 'В пятницу',
            '6' => 'В субботу',
            default => 'В воскресенье',
        };
    }
}
