<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Event;

use CmsIg\Seal\Adapter\AdapterInterface;
use Lochmueller\Seal\Dto\DsnDto;
use Lochmueller\Seal\Event\ResolveAdapterEvent;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class ResolveAdapterEventTest extends AbstractTest
{
    public function testConstructorSetsProperties(): void
    {
        $dsn = new DsnDto(scheme: 'elasticsearch', host: 'localhost', port: 9200);
        $site = $this->createStub(SiteInterface::class);

        $event = new ResolveAdapterEvent($dsn, $site);

        self::assertSame($dsn, $event->searchDsn);
        self::assertSame($site, $event->site);
        self::assertNull($event->adapter);
    }

    public function testConstructorWithAdapter(): void
    {
        $dsn = new DsnDto(scheme: 'loupe');
        $site = $this->createStub(SiteInterface::class);
        $adapter = $this->createStub(AdapterInterface::class);

        $event = new ResolveAdapterEvent($dsn, $site, $adapter);

        self::assertSame($adapter, $event->adapter);
    }

    public function testAdapterIsModifiable(): void
    {
        $dsn = new DsnDto(scheme: 'loupe');
        $site = $this->createStub(SiteInterface::class);

        $event = new ResolveAdapterEvent($dsn, $site);
        self::assertNull($event->adapter);

        $adapter = $this->createStub(AdapterInterface::class);
        $event->adapter = $adapter;

        self::assertSame($adapter, $event->adapter);
    }
}
