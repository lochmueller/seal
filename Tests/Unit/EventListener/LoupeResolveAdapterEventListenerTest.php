<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\EventListener;

use Lochmueller\Seal\Dto\DsnDto;
use Lochmueller\Seal\Event\ResolveAdapterEvent;
use Lochmueller\Seal\EventListener\LoupeResolveAdapterEventListener;
use Lochmueller\Seal\Exception\AdapterDependenciesNotFoundException;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class LoupeResolveAdapterEventListenerTest extends AbstractTest
{
    private LoupeResolveAdapterEventListener $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $environment = $this->createStub(Environment::class);
        $this->subject = new LoupeResolveAdapterEventListener($environment);
    }

    public function testSkipsNonLoupeScheme(): void
    {
        $dsn = new DsnDto(scheme: 'elasticsearch', host: 'localhost');
        $site = $this->createStub(SiteInterface::class);
        $event = new ResolveAdapterEvent($dsn, $site);

        $this->subject->resolveAdapter($event);

        self::assertNull($event->adapter);
    }

    public function testSkipsMeilisearchScheme(): void
    {
        $dsn = new DsnDto(scheme: 'meilisearch', host: 'localhost');
        $site = $this->createStub(SiteInterface::class);
        $event = new ResolveAdapterEvent($dsn, $site);

        $this->subject->resolveAdapter($event);

        self::assertNull($event->adapter);
    }

    public function testThrowsExceptionWhenLoupeAdapterFactoryNotAvailable(): void
    {
        if (class_exists(\CmsIg\Seal\Adapter\Loupe\LoupeAdapterFactory::class)) {
            self::markTestSkipped('LoupeAdapterFactory is available, cannot test missing dependency.');
        }

        $dsn = new DsnDto(scheme: 'loupe', host: 'var', path: '/indices');
        $site = $this->createStub(SiteInterface::class);
        $event = new ResolveAdapterEvent($dsn, $site);

        $this->expectException(AdapterDependenciesNotFoundException::class);
        $this->expectExceptionMessageMatches('/cmsig\/seal-loupe-adapter/');

        $this->subject->resolveAdapter($event);
    }
}
