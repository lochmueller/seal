<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Dashboard\Provider;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Engine;
use CmsIg\Seal\Schema\Field\IdentifierField;
use CmsIg\Seal\Schema\Field\TextField;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use Lochmueller\Seal\Dashboard\Provider\IndexDocumentCountDataProvider;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Lochmueller\Seal\Seal;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

if (!interface_exists(\TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface::class)) {
    require_once __DIR__ . '/Fixtures/ListDataProviderInterface.php';
}

class IndexDocumentCountDataProviderTest extends AbstractTest
{
    public function testGetItemsTransformsSiteAndIndexDataCorrectly(): void
    {
        $site = $this->createStub(Site::class);
        $site->method('getIdentifier')->willReturn('main');

        $siteFinder = $this->createStub(SiteFinder::class);
        $siteFinder->method('getAllSites')->willReturn(['main' => $site]);

        $index = new Index(SchemaBuilder::DEFAULT_INDEX, [
            'id' => new IdentifierField('id'),
            'title' => new TextField('title'),
        ]);
        $schema = new Schema([SchemaBuilder::DEFAULT_INDEX => $index]);

        $searcher = $this->createStub(SearcherInterface::class);
        $searcher->method('count')->willReturn(150);

        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('getSearcher')->willReturn($searcher);

        $engine = new Engine($adapter, $schema);

        $seal = $this->createStub(Seal::class);
        $seal->method('buildEngineBySite')->willReturn($engine);

        $schemaBuilder = $this->createStub(SchemaBuilder::class);
        $schemaBuilder->method('getSchema')->willReturn($schema);

        $subject = new IndexDocumentCountDataProvider($seal, $siteFinder, $schemaBuilder);
        $items = $subject->getItems();

        self::assertCount(1, $items);
        self::assertSame('main / ' . SchemaBuilder::DEFAULT_INDEX . ' / 150 / ' . $adapter::class, $items[0]);
    }

    public function testGetItemsReturnsEmptyArrayWhenNoSitesExist(): void
    {
        $siteFinder = $this->createStub(SiteFinder::class);
        $siteFinder->method('getAllSites')->willReturn([]);

        $seal = $this->createStub(Seal::class);
        $schemaBuilder = $this->createStub(SchemaBuilder::class);

        $subject = new IndexDocumentCountDataProvider($seal, $siteFinder, $schemaBuilder);

        self::assertSame([], $subject->getItems());
    }

    public function testGetItemsCatchesEngineErrorAndContinuesWithOtherSites(): void
    {
        $failingSite = $this->createStub(Site::class);
        $failingSite->method('getIdentifier')->willReturn('broken');

        $workingSite = $this->createStub(Site::class);
        $workingSite->method('getIdentifier')->willReturn('working');

        $siteFinder = $this->createStub(SiteFinder::class);
        $siteFinder->method('getAllSites')->willReturn([
            'broken' => $failingSite,
            'working' => $workingSite,
        ]);

        $index = new Index(SchemaBuilder::DEFAULT_INDEX, [
            'id' => new IdentifierField('id'),
            'title' => new TextField('title'),
        ]);
        $schema = new Schema([SchemaBuilder::DEFAULT_INDEX => $index]);

        $searcher = $this->createStub(SearcherInterface::class);
        $searcher->method('count')->willReturn(50);

        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('getSearcher')->willReturn($searcher);

        $workingEngine = new Engine($adapter, $schema);

        $seal = $this->createStub(Seal::class);
        $seal->method('buildEngineBySite')->willReturnCallback(
            static function (Site $site) use ($workingEngine): Engine {
                if ($site->getIdentifier() === 'broken') {
                    throw new \RuntimeException('Connection failed');
                }
                return $workingEngine;
            },
        );

        $schemaBuilder = $this->createStub(SchemaBuilder::class);
        $schemaBuilder->method('getSchema')->willReturn($schema);

        $subject = new IndexDocumentCountDataProvider($seal, $siteFinder, $schemaBuilder);
        $items = $subject->getItems();

        self::assertCount(2, $items);

        // First item: error entry for the broken site
        self::assertSame('broken / Error / Connection failed', $items[0]);

        // Second item: successful entry for the working site
        self::assertSame('working / ' . SchemaBuilder::DEFAULT_INDEX . ' / 50 / ' . $adapter::class, $items[1]);
    }
}
