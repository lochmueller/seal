<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\EventListener;

use CmsIg\Seal\EngineInterface;
use Lochmueller\Index\Enums\IndexTechnology;
use Lochmueller\Index\Event\DeIndexRecordEvent;
use Lochmueller\Seal\EventListener\DeIndexRecordEventListener;
use Lochmueller\Seal\Seal;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class DeIndexRecordEventListenerTest extends AbstractTest
{
    public function testMissingDocumentIsTreatedAsAlreadyDeIndexed(): void
    {
        $site = $this->createStub(SiteInterface::class);
        $engine = $this->createMock(EngineInterface::class);
        $engine->expects(self::once())
            ->method('deleteDocument')
            ->with('default', 'r-tx_example_domain_model_item-42-0')
            ->willThrowException(new class ('{"_index":"default","result":"not_found"}') extends \RuntimeException {
                public function getStatusCode(): int
                {
                    return 404;
                }
            });

        $seal = $this->createStub(Seal::class);
        $seal->method('buildEngineBySite')->willReturn($engine);
        $seal->method('getIndexNameBySite')->willReturn('default');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('debug')
            ->with('Record document was already absent during de-indexing.', self::arrayHasKey('recordUid'));
        $logger->expects(self::never())->method('error');

        $subject = new DeIndexRecordEventListener($seal);
        $subject->setLogger($logger);
        $subject(new DeIndexRecordEvent(
            site: $site,
            technology: IndexTechnology::Http,
            indexConfigurationRecordId: 7,
            indexProcessId: 'process-1',
            table: 'tx_example_domain_model_item',
            recordUid: 42,
            language: 0,
        ));
    }

    public function testNonDocumentNotFoundErrorsAreLoggedAndPropagated(): void
    {
        $engine = $this->createMock(EngineInterface::class);
        $engine->expects(self::once())
            ->method('deleteDocument')
            ->willThrowException(new class ('{"error":{"type":"index_not_found_exception"}}') extends \RuntimeException {
                public function getStatusCode(): int
                {
                    return 404;
                }
            });

        $seal = $this->createStub(Seal::class);
        $seal->method('buildEngineBySite')->willReturn($engine);
        $seal->method('getIndexNameBySite')->willReturn('default');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('debug');
        $logger->expects(self::once())->method('error');

        $subject = new DeIndexRecordEventListener($seal);
        $subject->setLogger($logger);
        $this->expectException(\RuntimeException::class);
        $subject(new DeIndexRecordEvent(
            site: $this->createStub(SiteInterface::class),
            technology: IndexTechnology::Http,
            indexConfigurationRecordId: 7,
            indexProcessId: 'process-1',
            table: 'tx_example_domain_model_item',
            recordUid: 42,
            language: 0,
        ));
    }
}
