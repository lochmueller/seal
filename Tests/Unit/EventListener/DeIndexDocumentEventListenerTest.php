<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\EventListener;

use CmsIg\Seal\EngineInterface;
use Lochmueller\Index\Event\DeIndexDocumentEvent;
use Lochmueller\Seal\Event\BeforePageDeleteEvent;
use Lochmueller\Seal\EventListener\DeIndexDocumentEventListener;
use Lochmueller\Seal\Seal;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class DeIndexDocumentEventListenerTest extends AbstractTest
{
    private const URI = 'https://example.com/page';

    public function testDeletesDocumentByUriHash(): void
    {
        $site = $this->createStub(SiteInterface::class);

        $engine = $this->createMock(EngineInterface::class);
        $engine->expects(self::once())
            ->method('deleteDocument')
            ->with('default', 'p-' . md5(self::URI));

        $dispatchedEvent = null;
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$dispatchedEvent): object {
                $dispatchedEvent = $event;
                return $event;
            });

        $subject = new DeIndexDocumentEventListener($this->createSeal($engine), $eventDispatcher);
        $subject(new DeIndexDocumentEvent($site, self::URI));

        self::assertInstanceOf(BeforePageDeleteEvent::class, $dispatchedEvent);
        self::assertSame(self::URI, $dispatchedEvent->uri);
        self::assertSame($site, $dispatchedEvent->site);
        self::assertSame('default', $dispatchedEvent->indexName);
    }

    public function testListenerCanChangeDocumentIdentifier(): void
    {
        $engine = $this->createMock(EngineInterface::class);
        $engine->expects(self::once())
            ->method('deleteDocument')
            ->with('default', 'custom-id');

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(static function (object $event): object {
            if ($event instanceof BeforePageDeleteEvent) {
                $event->documentIdentifier = 'custom-id';
            }
            return $event;
        });

        $subject = new DeIndexDocumentEventListener($this->createSeal($engine), $eventDispatcher);
        $subject(new DeIndexDocumentEvent($this->createStub(SiteInterface::class), self::URI));
    }

    public function testListenerCanPreventDeletion(): void
    {
        $engine = $this->createMock(EngineInterface::class);
        $engine->expects(self::never())->method('deleteDocument');

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(static function (object $event): object {
            if ($event instanceof BeforePageDeleteEvent) {
                $event->deletePage = false;
            }
            return $event;
        });

        $subject = new DeIndexDocumentEventListener($this->createSeal($engine), $eventDispatcher);
        $subject(new DeIndexDocumentEvent($this->createStub(SiteInterface::class), self::URI));
    }

    public function testLogsExceptionInsteadOfThrowing(): void
    {
        $seal = $this->createStub(Seal::class);
        $seal->method('buildEngineBySite')->willThrowException(new \RuntimeException('Engine not available'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with('Engine not available', self::arrayHasKey('exception'));

        $subject = new DeIndexDocumentEventListener($seal, $this->createStub(EventDispatcherInterface::class));
        $subject->setLogger($logger);
        $subject(new DeIndexDocumentEvent($this->createStub(SiteInterface::class), self::URI));
    }

    private function createSeal(EngineInterface $engine): Seal
    {
        $seal = $this->createStub(Seal::class);
        $seal->method('buildEngineBySite')->willReturn($engine);
        $seal->method('getIndexNameBySite')->willReturn('default');
        return $seal;
    }
}
