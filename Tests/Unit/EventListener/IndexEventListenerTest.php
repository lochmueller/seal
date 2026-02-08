<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\EventListener;

use CmsIg\Seal\EngineInterface;
use Lochmueller\Index\Enums\IndexTechnology;
use Lochmueller\Index\Enums\IndexType;
use Lochmueller\Index\Event\IndexFileEvent;
use Lochmueller\Index\Event\IndexPageEvent;
use Lochmueller\Index\Traversing\RecordSelection;
use Lochmueller\Seal\EventListener\IndexEventListener;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Lochmueller\Seal\Seal;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class IndexEventListenerTest extends AbstractTest
{
    private Seal $sealStub;

    private IndexEventListener $subject;

    private RecordSelection $recordSelectionStub;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sealStub = $this->createStub(Seal::class);
        $resourceFactoryStub = $this->createStub(ResourceFactory::class);
        $this->recordSelectionStub = $this->createStub(RecordSelection::class);

        $eventDispatcherStub = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcherStub->method('dispatch')->willReturnArgument(0);

        $this->subject = new IndexEventListener(
            $this->sealStub,
            $resourceFactoryStub,
            $this->recordSelectionStub,
            $eventDispatcherStub,
        );
    }

    public function testInvokeWithPageEventSavesDocument(): void
    {
        $site = $this->createStub(SiteInterface::class);
        $site->method('getIdentifier')->willReturn('main');

        $event = new IndexPageEvent(
            site: $site,
            technology: IndexTechnology::Frontend,
            type: IndexType::Full,
            indexConfigurationRecordId: 1,
            indexProcessId: 'proc-1',
            language: 0,
            title: 'Test Page',
            content: '<p>Hello World</p>',
            pageUid: 42,
            accessGroups: [],
            uri: 'https://example.com/test',
        );

        $engine = $this->createMock(EngineInterface::class);
        $this->sealStub->method('buildEngineBySite')->willReturn($engine);

        $engine->expects(self::once())
            ->method('saveDocument')
            ->with(
                SchemaBuilder::DEFAULT_INDEX,
                self::callback(fn(array $document): bool => $document['title'] === 'Test Page'
                        && $document['content'] === 'Hello World'
                        && $document['site'] === 'main'
                        && $document['language'] === '0'
                        && $document['uri'] === 'https://example.com/test'
                        && str_starts_with($document['id'], 'p-')
                        && in_array('Page', $document['tags'], true)),
            );

        ($this->subject)($event);
    }

    public function testInvokeWithFileEventSavesDocument(): void
    {
        $site = $this->createStub(SiteInterface::class);
        $site->method('getIdentifier')->willReturn('main');

        $event = new IndexFileEvent(
            site: $site,
            indexConfigurationRecordId: 1,
            indexProcessId: 'proc-1',
            title: 'Test File',
            content: 'File content here',
            fileIdentifier: '1:/documents/test.pdf',
            uri: 'https://example.com/file.pdf',
        );

        $engine = $this->createMock(EngineInterface::class);
        $this->sealStub->method('buildEngineBySite')->willReturn($engine);

        $engine->expects(self::once())
            ->method('saveDocument')
            ->with(
                SchemaBuilder::DEFAULT_INDEX,
                self::callback(fn(array $document): bool => $document['title'] === 'Test File'
                        && str_starts_with($document['id'], 'd-')
                        && in_array('File', $document['tags'], true)),
            );

        ($this->subject)($event);
    }

    public function testInvokeLogsExceptionOnEngineFailure(): void
    {
        $site = $this->createStub(SiteInterface::class);

        $event = new IndexPageEvent(
            site: $site,
            technology: IndexTechnology::Frontend,
            type: IndexType::Full,
            indexConfigurationRecordId: 1,
            indexProcessId: 'proc-1',
            language: 0,
            title: 'Fail Page',
            content: 'content',
            pageUid: 1,
            accessGroups: [],
        );

        $this->sealStub->method('buildEngineBySite')
            ->willThrowException(new \Exception('Engine error'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with('Engine error', self::arrayHasKey('exception'));

        $this->subject->setLogger($logger);

        ($this->subject)($event);
    }

    public function testInvokeWithPageEventAndEmptyUriResolvesViaRouter(): void
    {
        $router = $this->createStub(\TYPO3\CMS\Core\Routing\RouterInterface::class);
        $router->method('generateUri')->willReturn(new \TYPO3\CMS\Core\Http\Uri('https://example.com/resolved'));

        $site = $this->createStub(Site::class);
        $site->method('getIdentifier')->willReturn('main');
        $site->method('getRouter')->willReturn($router);

        $event = new IndexPageEvent(
            site: $site,
            technology: IndexTechnology::Frontend,
            type: IndexType::Full,
            indexConfigurationRecordId: 1,
            indexProcessId: 'proc-1',
            language: 0,
            title: 'Resolved Page',
            content: 'content',
            pageUid: 42,
            accessGroups: [],
            uri: '',
        );

        $engine = $this->createMock(EngineInterface::class);
        $this->sealStub->method('buildEngineBySite')->willReturn($engine);

        $engine->expects(self::once())
            ->method('saveDocument')
            ->with(
                SchemaBuilder::DEFAULT_INDEX,
                self::callback(fn(array $doc): bool => $doc['uri'] === 'https://example.com/resolved'),
            );

        ($this->subject)($event);
    }

    public function testInvokeWithPageEventIncludesKeywordsAsTags(): void
    {
        $site = $this->createStub(SiteInterface::class);
        $site->method('getIdentifier')->willReturn('main');

        $event = new IndexPageEvent(
            site: $site,
            technology: IndexTechnology::Frontend,
            type: IndexType::Full,
            indexConfigurationRecordId: 1,
            indexProcessId: 'proc-1',
            language: 0,
            title: 'Tagged Page',
            content: 'content',
            pageUid: 10,
            accessGroups: [],
            uri: 'https://example.com/tagged',
        );

        $this->recordSelectionStub
            ->method('findRenderablePage')
            ->willReturn(['keywords' => 'typo3, search, seal']);

        $engine = $this->createMock(EngineInterface::class);
        $this->sealStub->method('buildEngineBySite')->willReturn($engine);

        $engine->expects(self::once())
            ->method('saveDocument')
            ->with(
                SchemaBuilder::DEFAULT_INDEX,
                self::callback(fn(array $doc): bool => in_array('Page', $doc['tags'], true)
                        && in_array('typo3', $doc['tags'], true)
                        && in_array('search', $doc['tags'], true)
                        && in_array('seal', $doc['tags'], true)),
            );

        ($this->subject)($event);
    }

    public function testInvokeStripsHtmlTagsFromContent(): void
    {
        $site = $this->createStub(SiteInterface::class);
        $site->method('getIdentifier')->willReturn('main');

        $event = new IndexPageEvent(
            site: $site,
            technology: IndexTechnology::Frontend,
            type: IndexType::Full,
            indexConfigurationRecordId: 1,
            indexProcessId: 'proc-1',
            language: 0,
            title: 'HTML Page',
            content: '<h1>Title</h1>  <p>Paragraph   text</p>',
            pageUid: 1,
            accessGroups: [],
            uri: 'https://example.com',
        );

        $engine = $this->createMock(EngineInterface::class);
        $this->sealStub->method('buildEngineBySite')->willReturn($engine);

        $engine->expects(self::once())
            ->method('saveDocument')
            ->with(
                SchemaBuilder::DEFAULT_INDEX,
                self::callback(fn(array $doc): bool => $doc['content'] === 'Title Paragraph text'),
            );

        ($this->subject)($event);
    }
}
