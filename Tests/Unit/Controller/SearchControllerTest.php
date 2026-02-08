<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Controller;

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Schema\Field\IdentifierField;
use CmsIg\Seal\Schema\Field\TextField;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use CmsIg\Seal\Search\Facet\CountFacet;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\Search;
use CmsIg\Seal\Search\SearchBuilder;
use Lochmueller\Seal\Configuration\Configuration;
use Lochmueller\Seal\Configuration\ConfigurationLoader;
use Lochmueller\Seal\Controller\SearchController;
use Lochmueller\Seal\Filter\Filter;
use Lochmueller\Seal\Filter\TagConfigurationParser;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Lochmueller\Seal\Seal;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;

/**
 * Unit tests for SearchController faceting logic.
 *
 * Validates: Requirements 3.1, 3.2, 3.3
 */
class SearchControllerTest extends AbstractTest
{
    private ?Search $capturedSearch = null;

    /**
     * @var array<string, mixed>
     */
    private array $capturedViewVariables = [];

    /**
     * Validates: Requirement 3.1
     * Test: tagCondition vorhanden → Facet wird hinzugefügt
     */
    public function testSearchActionAddsFacetWhenTagConditionPresent(): void
    {
        $filterRows = [
            ['uid' => 1, 'type' => 'searchCondition', 'sorting' => 1],
            ['uid' => 2, 'type' => 'tagCondition', 'tags' => "page=Seiten\nfile=Dateien", 'sorting' => 2],
        ];

        $facetData = ['tags' => ['count' => ['page' => 42, 'file' => 15]]];

        $subject = $this->buildController($filterRows, $facetData);
        $subject->searchAction();

        self::assertNotNull($this->capturedSearch, 'Search should have been executed');
        self::assertCount(1, $this->capturedSearch->facets);
        self::assertInstanceOf(CountFacet::class, $this->capturedSearch->facets[0]);
        self::assertSame('tags', $this->capturedSearch->facets[0]->field);
    }

    /**
     * Validates: Requirement 3.3
     * Test: kein tagCondition → kein Facet
     */
    public function testSearchActionDoesNotAddFacetWithoutTagCondition(): void
    {
        $filterRows = [
            ['uid' => 1, 'type' => 'searchCondition', 'sorting' => 1],
            ['uid' => 3, 'type' => 'geoDistanceCondition', 'sorting' => 2],
        ];

        $subject = $this->buildController($filterRows, []);
        $subject->searchAction();

        self::assertNotNull($this->capturedSearch, 'Search should have been executed');
        self::assertCount(0, $this->capturedSearch->facets);
    }

    /**
     * Validates: Requirement 3.2
     * Test: Facetten-Daten werden korrekt an View übergeben
     */
    public function testSearchActionPassesFacetDataToView(): void
    {
        $filterRows = [
            ['uid' => 1, 'type' => 'tagCondition', 'tags' => "page=Seiten", 'sorting' => 1],
        ];

        $facetData = ['tags' => ['count' => ['page' => 42, 'file' => 15]]];

        $subject = $this->buildController($filterRows, $facetData);
        $subject->searchAction();

        self::assertArrayHasKey('tagFacets', $this->capturedViewVariables);
        self::assertSame(['count' => ['page' => 42, 'file' => 15]], $this->capturedViewVariables['tagFacets']);
    }


    /**
     * Validates: Requirement 3.2
     * Test: No tags key in facets → empty array passed to view
     */
    public function testSearchActionPassesEmptyTagFacetsWhenNoTagsInResult(): void
    {
        $filterRows = [
            ['uid' => 1, 'type' => 'tagCondition', 'tags' => "page=Seiten", 'sorting' => 1],
        ];

        $subject = $this->buildController($filterRows, []);
        $subject->searchAction();

        self::assertArrayHasKey('tagFacets', $this->capturedViewVariables);
        self::assertSame([], $this->capturedViewVariables['tagFacets']);
    }

    /**
     * Validates: Requirements 4.1, 4.3
     * Test: parsedTags with facet counts are attached to tagCondition filter rows
     */
    public function testSearchActionEnrichesFilterRowsWithParsedTags(): void
    {
        $filterRows = [
            ['uid' => 1, 'type' => 'tagCondition', 'tags' => "page=Seiten\nfile=Dateien", 'sorting' => 1],
        ];

        $facetData = ['tags' => ['count' => ['page' => 42]]];

        $subject = $this->buildController($filterRows, $facetData);
        $subject->searchAction();

        $filters = $this->capturedViewVariables['filters'];
        self::assertArrayHasKey('parsedTags', $filters[0]);
        self::assertCount(2, $filters[0]['parsedTags']);
        self::assertSame('page', $filters[0]['parsedTags'][0]['value']);
        self::assertSame('Seiten', $filters[0]['parsedTags'][0]['label']);
        self::assertSame(42, $filters[0]['parsedTags'][0]['count']);
        self::assertSame('file', $filters[0]['parsedTags'][1]['value']);
        self::assertSame(0, $filters[0]['parsedTags'][1]['count']);
    }

    /**
     * Validates: Requirement 4.2
     * Test: selected tags from request are marked as selected in parsedTags
     */
    public function testSearchActionMarkesSelectedTagsInParsedTags(): void
    {
        $filterRows = [
            ['uid' => 5, 'type' => 'tagCondition', 'tags' => "page=Seiten\nfile=Dateien", 'sorting' => 1],
        ];

        $facetData = ['tags' => ['count' => ['page' => 10, 'file' => 3]]];

        $subject = $this->buildController($filterRows, $facetData, ['tx_seal_search' => ['field_5' => ['page']]]);
        $subject->searchAction();

        $filters = $this->capturedViewVariables['filters'];
        self::assertTrue($filters[0]['parsedTags'][0]['selected']);
        self::assertFalse($filters[0]['parsedTags'][1]['selected']);
    }

    /**
     * @param array<int, array<string, mixed>> $filterRows
     * @param array<string, mixed> $facetData
     * @param array<string, mixed>|null $parsedBody
     */
    private function buildController(array $filterRows, array $facetData, ?array $parsedBody = null): SearchController
    {
        $this->capturedSearch = null;
        $this->capturedViewVariables = [];

        // Build a minimal Index and Schema for the SearchBuilder
        $index = new Index(SchemaBuilder::DEFAULT_INDEX, [
            'id' => new IdentifierField('id'),
            'title' => new TextField('title'),
            'tags' => new TextField('tags', multiple: true, filterable: true, facet: true),
        ]);
        $schema = new Schema([SchemaBuilder::DEFAULT_INDEX => $index]);

        // Mock SearcherInterface to capture the Search and return a controlled Result
        $searcher = $this->createMock(SearcherInterface::class);
        $searcher->method('search')
            ->willReturnCallback(function (Search $search) use ($facetData): Result {
                $this->capturedSearch = $search;
                return new Result(
                    (static function (): \Generator {
                        yield from [];
                    })(),
                    0,
                    $facetData,
                );
            });

        // Create a real SearchBuilder (final class, can't mock)
        $searchBuilder = new SearchBuilder($schema, $searcher);
        $searchBuilder->index(SchemaBuilder::DEFAULT_INDEX);

        // Mock EngineInterface
        $engine = $this->createStub(EngineInterface::class);
        $engine->method('createSearchBuilder')->willReturn($searchBuilder);

        // Mock Seal
        $seal = $this->createStub(Seal::class);
        $seal->method('buildEngineBySite')->willReturn($engine);

        // Mock ConfigurationLoader
        $config = new Configuration('typo3://', 3, 10);
        $configLoader = $this->createStub(ConfigurationLoader::class);
        $configLoader->method('loadBySite')->willReturn($config);

        // Mock Filter service (returns input array unchanged)
        $filter = $this->createStub(Filter::class);
        $filter->method('addFilterConfiguration')->willReturnArgument(0);

        // Create controller with a partial mock to override getFilterRowsByContentElementUid
        $tagConfigurationParser = new TagConfigurationParser();
        $subject = $this->getMockBuilder(SearchController::class)
            ->setConstructorArgs([$seal, $configLoader, $filter, $tagConfigurationParser])
            ->onlyMethods(['getFilterRowsByContentElementUid', 'getCurrentContentElementRow'])
            ->getMock();

        $subject->method('getCurrentContentElementRow')->willReturn(['uid' => 100]);
        $subject->method('getFilterRowsByContentElementUid')->willReturn(new \ArrayIterator($filterRows));

        // Set up the Extbase request with required attributes
        $site = $this->createStub(Site::class);
        $site->method('getIdentifier')->willReturn('main');

        $siteLanguage = $this->createStub(SiteLanguage::class);
        $siteLanguage->method('getLanguageId')->willReturn(0);

        $extbaseParams = new ExtbaseRequestParameters();
        $serverRequest = new ServerRequest('https://example.com/', 'GET');
        $serverRequest = $serverRequest
            ->withAttribute('extbase', $extbaseParams)
            ->withAttribute('site', $site)
            ->withAttribute('language', $siteLanguage)
            ->withAttribute('currentContentObject', $this->createContentObjectStub())
            ->withParsedBody($parsedBody);

        $extbaseRequest = new Request($serverRequest);

        // Set protected properties via reflection
        $reflection = new \ReflectionClass($subject);

        $requestProp = $reflection->getProperty('request');
        $requestProp->setValue($subject, $extbaseRequest);

        // Mock ViewInterface to capture assigned variables
        $view = $this->createMock(ViewInterface::class);
        $view->method('assignMultiple')
            ->willReturnCallback(function (array $values) use ($view): ViewInterface {
                $this->capturedViewVariables = array_merge($this->capturedViewVariables, $values);
                return $view;
            });
        $view->method('render')->willReturn('');

        $viewProp = $reflection->getProperty('view');
        $viewProp->setValue($subject, $view);

        // Set up response factory for htmlResponse()
        $stream = $this->createStub(StreamInterface::class);
        $streamFactory = $this->createStub(StreamFactoryInterface::class);
        $streamFactory->method('createStream')->willReturn($stream);

        $response = $this->createStub(ResponseInterface::class);
        $response->method('withHeader')->willReturn($response);
        $response->method('withBody')->willReturn($response);

        $responseFactory = $this->createStub(ResponseFactoryInterface::class);
        $responseFactory->method('createResponse')->willReturn($response);

        $subject->injectResponseFactory($responseFactory);
        $subject->injectStreamFactory($streamFactory);

        return $subject;
    }

    private function createContentObjectStub(): object
    {
        return new class {
            /** @var array<string, mixed> */
            public array $data = ['uid' => 100];
        };
    }
}
