<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Event;

use Lochmueller\Seal\Event\BeforeSaveDocumentEvent;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class BeforeSaveDocumentEventTest extends AbstractTest
{
    public function testConstructorSetsProperties(): void
    {
        $site = $this->createStub(SiteInterface::class);
        $document = ['id' => 'p-1', 'title' => 'Home'];

        $event = new BeforeSaveDocumentEvent($document, $site, 'default');

        self::assertSame($document, $event->document);
        self::assertSame($site, $event->site);
        self::assertSame('default', $event->indexName);
    }

    public function testDocumentIsModifiable(): void
    {
        $event = new BeforeSaveDocumentEvent(['id' => 'p-1'], $this->createStub(SiteInterface::class), 'default');

        $event->document['title'] = 'Changed';

        self::assertSame(['id' => 'p-1', 'title' => 'Changed'], $event->document);
    }
}
