<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Event;

use Lochmueller\Seal\Event\BeforePageDeleteEvent;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class BeforePageDeleteEventTest extends AbstractTest
{
    public function testConstructorSetsProperties(): void
    {
        $site = $this->createStub(SiteInterface::class);

        $event = new BeforePageDeleteEvent('p-123', 'https://example.com/page', $site, 'default');

        self::assertSame('p-123', $event->documentIdentifier);
        self::assertSame('https://example.com/page', $event->uri);
        self::assertSame($site, $event->site);
        self::assertSame('default', $event->indexName);
        self::assertTrue($event->deletePage);
    }

    public function testMutablePropertiesAreModifiable(): void
    {
        $event = new BeforePageDeleteEvent('p-123', 'https://example.com/page', $this->createStub(SiteInterface::class), 'default');

        $event->documentIdentifier = 'p-456';
        $event->uri = 'https://example.com/other';
        $event->deletePage = false;

        self::assertSame('p-456', $event->documentIdentifier);
        self::assertSame('https://example.com/other', $event->uri);
        self::assertFalse($event->deletePage);
    }
}
