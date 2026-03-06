<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\EventListener;

use Lochmueller\Seal\Dto\DsnDto;
use Lochmueller\Seal\Event\AfterDsnParsedEvent;
use Lochmueller\Seal\EventListener\LoupeDsnPathHandlingEventListener;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

class LoupeDsnPathHandlingEventListenerTest extends AbstractTest
{
    private LoupeDsnPathHandlingEventListener $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new LoupeDsnPathHandlingEventListener();
    }

    public function testSkipsNonLoupeScheme(): void
    {
        $dsn = new DsnDto(scheme: 'elasticsearch', host: 'localhost');
        $event = new AfterDsnParsedEvent($dsn, 'elasticsearch://localhost');

        $this->subject->__invoke($event);

        self::assertSame('localhost', $event->dsnDto->host);
    }

    public function testSkipsMeilisearchScheme(): void
    {
        $dsn = new DsnDto(scheme: 'meilisearch', host: 'localhost');
        $event = new AfterDsnParsedEvent($dsn, 'meilisearch://localhost');

        $this->subject->__invoke($event);

        self::assertSame('localhost', $event->dsnDto->host);
    }

    public function testRewritesDsnForLoupeScheme(): void
    {
        $dsn = new DsnDto(scheme: 'loupe', host: 'var', path: 'indices');
        $event = new AfterDsnParsedEvent($dsn, 'loupe://var/indices');

        $this->subject->__invoke($event);

        $projectPath = \TYPO3\CMS\Core\Core\Environment::getProjectPath();
        self::assertSame($projectPath . '/var/indices', $event->dsnDto->host);
        self::assertSame('loupe', $event->dsnDto->scheme);
    }
}
