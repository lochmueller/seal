<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Engine;

use CmsIg\Seal\Adapter\AdapterFactory;
use CmsIg\Seal\Adapter\AdapterFactoryInterface;
use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use Lochmueller\Index\Configuration\Configuration as IndexConfiguration;
use Lochmueller\Index\Configuration\ConfigurationLoader as IndexConfigurationLoader;
use Lochmueller\Index\Enums\IndexTechnology;
use Lochmueller\Seal\Configuration\Configuration;
use Lochmueller\Seal\Configuration\ConfigurationLoader;
use Lochmueller\Seal\DsnNormalizer;
use Lochmueller\Seal\DsnParser;
use Lochmueller\Seal\Dto\DsnDto;
use Lochmueller\Seal\Engine\EngineFactory;
use Lochmueller\Seal\Event\ResolveAdapterEvent;
use Lochmueller\Seal\Exception\AdapterNotFoundException;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use Lochmueller\Index\Indexing\Record\RecordFieldDefinitionResolver;
use Lochmueller\Index\Indexing\Record\RecordSourceResolver;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class EngineFactoryTest extends AbstractTest
{
    private ConfigurationLoader $configurationLoaderStub;

    private IndexConfigurationLoader $indexConfigurationLoaderStub;

    private SchemaBuilder $schemaBuilderStub;

    private DsnParser $dsnParserStub;

    private RecordFieldDefinitionResolver $recordFieldDefinitionResolverStub;

    private RecordSourceResolver $recordSourceResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurationLoaderStub = $this->createStub(ConfigurationLoader::class);
        $this->indexConfigurationLoaderStub = $this->createStub(IndexConfigurationLoader::class);
        $this->indexConfigurationLoaderStub->method('loadAllBySite')->willReturn([]);
        $this->schemaBuilderStub = $this->createStub(SchemaBuilder::class);
        $this->schemaBuilderStub->method('getSchema')->willReturn(new Schema([
            SchemaBuilder::DEFAULT_INDEX => new Index(SchemaBuilder::DEFAULT_INDEX, [
                'id' => new Field\IdentifierField('id'),
            ]),
        ]));
        $this->dsnParserStub = $this->createStub(DsnParser::class);
        $this->recordFieldDefinitionResolverStub = new RecordFieldDefinitionResolver($this->createStub(TcaSchemaFactory::class));
        $this->recordSourceResolver = new RecordSourceResolver();
    }

    public function testBuildEngineBySiteReturnsEngineWhenAdapterFactoryMatches(): void
    {
        $dsn = new DsnDto(scheme: 'elasticsearch', host: 'localhost', port: 9200);
        $config = new Configuration('elasticsearch://localhost:9200', 3, 10);

        $this->configurationLoaderStub->method('loadBySite')->willReturn($config);
        $this->dsnParserStub->method('parse')->willReturn($dsn);

        $adapter = $this->createStub(AdapterInterface::class);
        $adapterFactory = new class ($adapter) implements AdapterFactoryInterface {
            private AdapterInterface $adapter;
            public array $dsn = [];

            public function __construct(AdapterInterface $adapter)
            {
                $this->adapter = $adapter;
            }

            public static function getName(): string
            {
                return 'elasticsearch';
            }

            public function createAdapter(array $dsn): AdapterInterface
            {
                $this->dsn = $dsn;
                return $this->adapter;
            }
        };

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')
            ->willReturnCallback(fn(ResolveAdapterEvent $event): ResolveAdapterEvent => $event);

        $subject = new EngineFactory(
            $eventDispatcher,
            $this->configurationLoaderStub,
            $this->indexConfigurationLoaderStub,
            $this->schemaBuilderStub,
            $this->dsnParserStub,
            new DsnNormalizer(),
            new AdapterFactory([
                'elasticsearch' => $adapterFactory,
            ]),
            $this->recordFieldDefinitionResolverStub,
            $this->recordSourceResolver,
        );

        $site = $this->createStub(SiteInterface::class);
        $site->method('getIdentifier')->willReturn('main');

        $result = $subject->buildEngineBySite($site);

        self::assertInstanceOf(EngineInterface::class, $result);

        // Verify AdapterFactory was called with correct DSN
        self::assertNotEmpty($adapterFactory->dsn);
        self::assertEquals('elasticsearch', $adapterFactory->dsn['scheme']);
        self::assertEquals('localhost', $adapterFactory->dsn['host']);
        self::assertEquals(9200, $adapterFactory->dsn['port']);
    }

    public function testBuildEngineBySitePassesNormalizedLoupeDsnToAdapterFactory(): void
    {
        $dsn = new DsnDto(scheme: 'loupe');
        $config = new Configuration('loupe://', 3, 10);

        $this->configurationLoaderStub->method('loadBySite')->willReturn($config);
        $this->dsnParserStub->method('parse')->willReturn($dsn);

        $adapter = $this->createStub(AdapterInterface::class);
        $adapterFactory = new class ($adapter) implements AdapterFactoryInterface {
            private AdapterInterface $adapter;
            public array $dsn = [];

            public function __construct(AdapterInterface $adapter)
            {
                $this->adapter = $adapter;
            }

            public static function getName(): string
            {
                return 'loupe';
            }

            public function createAdapter(array $dsn): AdapterInterface
            {
                $this->dsn = $dsn;
                return $this->adapter;
            }
        };

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnArgument(0);

        $subject = new EngineFactory(
            $eventDispatcher,
            $this->configurationLoaderStub,
            $this->indexConfigurationLoaderStub,
            $this->schemaBuilderStub,
            $this->dsnParserStub,
            new DsnNormalizer(),
            new AdapterFactory([
                'loupe' => $adapterFactory,
            ]),
            $this->recordFieldDefinitionResolverStub,
            $this->recordSourceResolver,
        );

        $site = $this->createStub(SiteInterface::class);
        $site->method('getIdentifier')->willReturn('main');

        $subject->buildEngineBySite($site);

        // The Loupe adapter builds its directory out of host and path and resolves a relative
        // one against the current working directory, which differs between CLI and frontend.
        self::assertSame(
            Environment::getProjectPath() . '/' . DsnNormalizer::LOUPE_DEFAULT_DIRECTORY,
            ($adapterFactory->dsn['host'] ?? '') . ($adapterFactory->dsn['path'] ?? ''),
        );
    }

    public function testBuildEngineBySiteThrowsExceptionWhenNoAdapterFound(): void
    {
        $dsn = new DsnDto(scheme: 'unknown');
        $config = new Configuration('unknown://', 3, 10);

        $this->configurationLoaderStub->method('loadBySite')->willReturn($config);
        $this->dsnParserStub->method('parse')->willReturn($dsn);

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')
            ->willReturnCallback(fn(ResolveAdapterEvent $event): ResolveAdapterEvent => $event);

        $subject = new EngineFactory(
            $eventDispatcher,
            $this->configurationLoaderStub,
            $this->indexConfigurationLoaderStub,
            $this->schemaBuilderStub,
            $this->dsnParserStub,
            new DsnNormalizer(),
            new AdapterFactory([]),
            $this->recordFieldDefinitionResolverStub,
            $this->recordSourceResolver,
        );

        $site = $this->createStub(SiteInterface::class);
        $site->method('getIdentifier')->willReturn('main');

        $this->expectException(AdapterNotFoundException::class);
        $this->expectExceptionCode(23482934);

        $subject->buildEngineBySite($site);
    }

    public function testBuildEngineBySiteUsesEventDispatcherToResolveAdapter(): void
    {
        $dsn = new DsnDto(scheme: 'custom');
        $config = new Configuration('custom://', 3, 10);

        $this->configurationLoaderStub->method('loadBySite')->willReturn($config);
        $this->dsnParserStub->method('parse')->willReturn($dsn);

        $adapter = $this->createStub(AdapterInterface::class);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ResolveAdapterEvent::class))
            ->willReturnCallback(function (ResolveAdapterEvent $event) use ($adapter): ResolveAdapterEvent {
                $event->adapter = $adapter;
                return $event;
            });

        $subject = new EngineFactory(
            $eventDispatcher,
            $this->configurationLoaderStub,
            $this->indexConfigurationLoaderStub,
            $this->schemaBuilderStub,
            $this->dsnParserStub,
            new DsnNormalizer(),
            new AdapterFactory([]),
            $this->recordFieldDefinitionResolverStub,
            $this->recordSourceResolver,
        );

        $site = $this->createStub(SiteInterface::class);
        $site->method('getIdentifier')->willReturn('main');

        $result = $subject->buildEngineBySite($site);

        self::assertInstanceOf(EngineInterface::class, $result);
    }

    public function testBuildEngineBySiteExceptionContainsSiteIdentifier(): void
    {
        $dsn = new DsnDto(scheme: 'missing');
        $config = new Configuration('missing://', 3, 10);

        $this->configurationLoaderStub->method('loadBySite')->willReturn($config);
        $this->dsnParserStub->method('parse')->willReturn($dsn);

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnArgument(0);

        $subject = new EngineFactory(
            $eventDispatcher,
            $this->configurationLoaderStub,
            $this->indexConfigurationLoaderStub,
            $this->schemaBuilderStub,
            $this->dsnParserStub,
            new DsnNormalizer(),
            new AdapterFactory([]),
            $this->recordFieldDefinitionResolverStub,
            $this->recordSourceResolver,
        );

        $site = $this->createStub(SiteInterface::class);
        $site->method('getIdentifier')->willReturn('my-portal');

        try {
            $subject->buildEngineBySite($site);
            self::fail('Expected AdapterNotFoundException was not thrown');
        } catch (AdapterNotFoundException $e) {
            self::assertStringContainsString('my-portal', $e->getMessage());
        }
    }

    public function testBuildEngineBySiteIncludesRecordFieldsFromIndexConfigurationsInSchema(): void
    {
        $site = $this->createStub(SiteInterface::class);
        $site->method('getIdentifier')->willReturn('main');

        $indexConfigurations = [];
        foreach ([IndexTechnology::Database, IndexTechnology::Frontend, IndexTechnology::Http] as $technology) {
            $indexConfigurations[] = new IndexConfiguration(
                configurationId: 7 + count($indexConfigurations),
                pageId: 1,
                technology: $technology,
                skipNoSearchPages: false,
                contentIndexing: true,
                levels: 0,
                fileMounts: [],
                fileTypes: [],
                configuration: [
                    'records' => [[
                        'table' => 'tx_example_domain_model_item',
                        'recordStorages' => [12],
                        'fields' => ['departmentId' => 'department_id'],
                    ]],
                ],
                partialIndexing: [],
                languages: [],
            );
        }
        $indexConfigurationLoader = $this->createMock(IndexConfigurationLoader::class);
        $indexConfigurationLoader->expects(self::once())
            ->method('loadAllBySite')
            ->with($site)
            ->willReturn($indexConfigurations);

        $field = $this->createStub(FieldTypeInterface::class);
        $field->method('getType')->willReturn('input');
        $field->method('getConfiguration')->willReturn(['eval' => 'int']);
        $field->method('isSearchable')->willReturn(false);
        $tcaSchema = $this->createStub(TcaSchema::class);
        $tcaSchema->method('hasField')->willReturn(true);
        $tcaSchema->method('getField')->willReturn($field);
        $tcaSchemaFactory = $this->createStub(TcaSchemaFactory::class);
        $tcaSchemaFactory->method('get')->willReturn($tcaSchema);

        $schemaBuilder = $this->createMock(SchemaBuilder::class);
        $schemaBuilder->expects(self::once())
            ->method('getSchema')
            ->with(self::callback(static fn(array $definitions): bool => $definitions['departmentId']['type'] === 'integer'
                && $definitions['departmentId']['source'] === 'department_id'))
            ->willReturn(new Schema([]));

        $adapter = $this->createStub(AdapterInterface::class);
        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')
            ->willReturnCallback(static function (ResolveAdapterEvent $event) use ($adapter): ResolveAdapterEvent {
                $event->adapter = $adapter;
                return $event;
            });

        $this->configurationLoaderStub->method('loadBySite')->willReturn(new Configuration('custom://', 3, 10));
        $this->dsnParserStub->method('parse')->willReturn(new DsnDto(scheme: 'custom'));
        $factory = new EngineFactory(
            $eventDispatcher,
            $this->configurationLoaderStub,
            $indexConfigurationLoader,
            $schemaBuilder,
            $this->dsnParserStub,
            new DsnNormalizer(),
            new AdapterFactory([]),
            new RecordFieldDefinitionResolver($tcaSchemaFactory),
            new RecordSourceResolver(),
        );

        self::assertInstanceOf(EngineInterface::class, $factory->buildEngineBySite($site));
    }
}
