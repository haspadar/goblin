<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Daily;

use Goblin\Daily\DayLabel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DayLabelTest extends TestCase
{
    #[Test]
    public function returnsYesterdayForOneDay(): void
    {
        self::assertSame(
            'Вчера',
            (new DayLabel(1))->text(),
            'one day ago must return Вчера',
        );
    }

    #[Test]
    public function returnsRussianWeekdayForTwoDays(): void
    {
        $label = (new DayLabel(2))->text();

        self::assertMatchesRegularExpression(
            '/^В[о ]?\s?\S+/',
            $label,
            'two days ago must return a Russian weekday name',
        );
    }
}
