<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Engine;

use CmsIg\Seal\Adapter\AdapterFactoryInterface;
use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use Lochmueller\Seal\Configuration\Configuration;
use Lochmueller\Seal\Configuration\ConfigurationLoader;
use Lochmueller\Seal\DsnParser;
use Lochmueller\Seal\Dto\DsnDto;
use Lochmueller\Seal\Engine\EngineFactory;
use Lochmueller\Seal\Event\ResolveAdapterEvent;
use Lochmueller\Seal\Exception\AdapterNotFoundException;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class EngineFactoryTest extends AbstractTest
{
    private Context $contextStub;

    private ConfigurationLoader $configurationLoaderStub;

    private SchemaBuilder $schemaBuilderStub;

    private DsnParser $dsnParserStub;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contextStub = $this->createStub(Context::class);
        $this->configurationLoaderStub = $this->createStub(ConfigurationLoader::class);
        $this->schemaBuilderStub = $this->createStub(SchemaBuilder::class);
        $this->schemaBuilderStub->method('getSchema')->willReturn(new Schema([
            SchemaBuilder::DEFAULT_INDEX => new Index(SchemaBuilder::DEFAULT_INDEX, [
                'id' => new Field\IdentifierField('id'),
            ]),
        ]));
        $this->dsnParserStub = $this->createStub(DsnParser::class);
    }

    public function testBuildEngineBySiteReturnsEngineWhenAdapterFactoryMatches(): void
    {
        $dsn = new DsnDto(scheme: 'elasticsearch', host: 'localhost', port: 9200);
        $config = new Configuration('elasticsearch://localhost:9200', 3, 10);

        $this->configurationLoaderStub->method('loadBySite')->willReturn($config);
        $this->dsnParserStub->method('parse')->willReturn($dsn);

        $adapter = $this->createStub(AdapterInterface::class);

        $adapterFactory = $this->createStub(AdapterFactoryInterface::class);
        $adapterFactory->method('createAdapter')->willReturn($adapter);

        // Use a concrete class to provide getName() as a static method
        $adapterFactory = new class ($adapter) implements AdapterFactoryInterface {
            public function __construct(private readonly AdapterInterface $adapter) {}

            public static function getName(): string
            {
                return 'elasticsearch';
            }

            public function createAdapter(array $dsn): AdapterInterface
            {
                return $this->adapter;
            }
        };

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')
            ->willReturnCallback(fn(ResolveAdapterEvent $event): ResolveAdapterEvent => $event);

        $subject = new EngineFactory(
            $this->contextStub,
            $eventDispatcher,
            $this->configurationLoaderStub,
            $this->schemaBuilderStub,
            $this->dsnParserStub,
            [$adapterFactory],
        );

        $site = $this->createStub(SiteInterface::class);
        $site->method('getIdentifier')->willReturn('main');

        $result = $subject->buildEngineBySite($site);

        self::assertInstanceOf(EngineInterface::class, $result);
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
            $this->contextStub,
            $eventDispatcher,
            $this->configurationLoaderStub,
            $this->schemaBuilderStub,
            $this->dsnParserStub,
            [],
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
            $this->contextStub,
            $eventDispatcher,
            $this->configurationLoaderStub,
            $this->schemaBuilderStub,
            $this->dsnParserStub,
            [],
        );

        $site = $this->createStub(SiteInterface::class);
        $site->method('getIdentifier')->willReturn('main');

        $result = $subject->buildEngineBySite($site);

        self::assertInstanceOf(EngineInterface::class, $result);
    }

    public function testBuildEngineBySiteSkipsNonMatchingAdapterFactories(): void
    {
        $dsn = new DsnDto(scheme: 'loupe');
        $config = new Configuration('loupe://', 3, 10);

        $this->configurationLoaderStub->method('loadBySite')->willReturn($config);
        $this->dsnParserStub->method('parse')->willReturn($dsn);

        $nonMatchingFactory = new class implements AdapterFactoryInterface {
            public static function getName(): string
            {
                return 'elasticsearch';
            }

            public function createAdapter(array $dsn): AdapterInterface
            {
                throw new \RuntimeException('Should not be called');
            }
        };

        $adapter = $this->createStub(AdapterInterface::class);

        // Event listener provides the adapter
        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')
            ->willReturnCallback(function (ResolveAdapterEvent $event) use ($adapter): ResolveAdapterEvent {
                $event->adapter = $adapter;
                return $event;
            });

        $subject = new EngineFactory(
            $this->contextStub,
            $eventDispatcher,
            $this->configurationLoaderStub,
            $this->schemaBuilderStub,
            $this->dsnParserStub,
            [$nonMatchingFactory],
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
            $this->contextStub,
            $eventDispatcher,
            $this->configurationLoaderStub,
            $this->schemaBuilderStub,
            $this->dsnParserStub,
            [],
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
}
