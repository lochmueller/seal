<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Event;

use Lochmueller\Seal\Dto\DsnDto;
use Lochmueller\Seal\Event\AfterDsnParsedEvent;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

class AfterDsnParsedEventTest extends AbstractTest
{
    public function testConstructorSetsProperties(): void
    {
        $dsn = new DsnDto(scheme: 'loupe', path: 'var/indices');

        $event = new AfterDsnParsedEvent($dsn, 'loupe://var/indices');

        self::assertSame($dsn, $event->dsnDto);
        self::assertSame('loupe://var/indices', $event->originalDsn);
    }

    public function testDsnDtoIsReplaceable(): void
    {
        $event = new AfterDsnParsedEvent(new DsnDto(scheme: 'loupe'), 'loupe://');

        $replacement = new DsnDto(scheme: 'typo3');
        $event->dsnDto = $replacement;

        self::assertSame($replacement, $event->dsnDto);
    }
}
