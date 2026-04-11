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

        self::assertContains(
            $label,
            ['В понедельник', 'Во вторник', 'В среду', 'В четверг', 'В пятницу', 'В субботу', 'В воскресенье'],
            'two days ago must return a valid Russian weekday name',
        );
    }
}
