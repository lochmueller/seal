<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Controller;

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Schema\Field\IdentifierField;
use CmsIg\Seal\Schema\Field\TextField;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use CmsIg\Seal\Search\Condition\EqualCondition;
use CmsIg\Seal\Search\Facet\CountFacet;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\Search;
use CmsIg\Seal\Search\SearchBuilder;
use Lochmueller\Seal\Controller\StartController;
use Lochmueller\Seal\Filter\RadiusConfigurationParser;
use Lochmueller\Seal\Filter\TagConfigurationParser;
use Lochmueller\Seal\Resolver\SearchRequestDataResolver;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Lochmueller\Seal\Seal;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use PHPUnit\Framework\MockObject\MockObject;
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

class StartControllerTest extends AbstractTest
{
    private ?Search $capturedSearch = null;

    /**
     * @var array<string, mixed>
     */
    private array $capturedViewVariables = [];

    public function testStartActionRedirectsToSearchWhenInitialResultsAreEnabled(): void
    {
        $redirectResponse = $this->createStub(ResponseInterface::class);

        $subject = $this->buildController(
            [],
            [],
            ['uid' => 100, 'seal_show_initial_results' => 1],
            expectFilterRows: false,
        );
        $subject->expects(self::once())
            ->method('redirect')
            ->with('search', 'Search')
            ->willReturn($redirectResponse);

        self::assertSame($redirectResponse, $subject->startAction());
        self::assertNull($this->capturedSearch);
    }

    public function testStartActionDoesNotRedirectWhenInitialResultsAreDisabled(): void
    {
        $subject = $this->buildController([], [], ['uid' => 100, 'seal_show_initial_results' => 0]);
        $subject->expects(self::never())->method('redirect');

        $subject->startAction();

        self::assertNotNull($this->capturedSearch);
    }

    public function testStartActionFiltersBySiteAndLanguage(): void
    {
        $subject = $this->buildController([], []);
        $subject->startAction();

        self::assertNotNull($this->capturedSearch, 'Search should have been executed');
        self::assertCount(2, $this->capturedSearch->filters);
        self::assertInstanceOf(EqualCondition::class, $this->capturedSearch->filters[0]);
        self::assertSame('site', $this->capturedSearch->filters[0]->field);
        self::assertSame('main', $this->capturedSearch->filters[0]->value);
        self::assertInstanceOf(EqualCondition::class, $this->capturedSearch->filters[1]);
        self::assertSame('language', $this->capturedSearch->filters[1]->field);
        self::assertSame('0', $this->capturedSearch->filters[1]->value);
    }

    public function testStartActionAddsFacetWhenTagConditionPresent(): void
    {
        $filterRows = [
            ['uid' => 2, 'type' => 'tagCondition', 'tags' => "page=Seiten", 'sorting' => 1],
        ];

        $subject = $this->buildController($filterRows, []);
        $subject->startAction();

        self::assertNotNull($this->capturedSearch);
        self::assertCount(1, $this->capturedSearch->facets);
        self::assertInstanceOf(CountFacet::class, $this->capturedSearch->facets[0]);
        self::assertSame('tags', $this->capturedSearch->facets[0]->field);
    }

    public function testStartActionDoesNotAddFacetWithoutTagCondition(): void
    {
        $filterRows = [
            ['uid' => 1, 'type' => 'searchCondition', 'sorting' => 1],
        ];

        $subject = $this->buildController($filterRows, []);
        $subject->startAction();

        self::assertNotNull($this->capturedSearch);
        self::assertCount(0, $this->capturedSearch->facets);
    }

    public function testStartActionAssignsViewVariables(): void
    {
        $filterRows = [
            ['uid' => 5, 'type' => 'tagCondition', 'tags' => "page=Seiten\nfile=Dateien", 'sorting' => 1],
        ];
        $facetData = ['tags' => ['count' => ['page' => 42]]];
        $requestData = ['search' => 'TYPO3', 'field_5' => ['file']];

        $subject = $this->buildController($filterRows, $facetData, queryParams: [SearchRequestDataResolver::PARAM_KEY => $requestData]);
        $subject->startAction();

        self::assertSame('seal-100', $this->capturedViewVariables['sealId']);
        self::assertSame(SearchRequestDataResolver::PARAM_KEY, $this->capturedViewVariables['pluginNamespace']);
        self::assertSame($requestData, $this->capturedViewVariables['requestData']);
        self::assertSame([
            ['value' => 'page', 'label' => 'Seiten', 'count' => 42, 'selected' => false],
            ['value' => 'file', 'label' => 'Dateien', 'count' => 0, 'selected' => true],
        ], $this->capturedViewVariables['filters'][0]['parsedTags']);
    }

    public function testStartActionAcceptsContentElementUidAsString(): void
    {
        // Database drivers may return the uid as string, which must not lead to a TypeError.
        $subject = $this->buildController([], [], ['uid' => '100']);
        $subject->startAction();

        self::assertSame('seal-100', $this->capturedViewVariables['sealId']);
    }

    /**
     * @param array<int, array<string, mixed>> $filterRows
     * @param array<string, mixed> $facetData
     * @param array<string, mixed> $contentElementRow
     * @param array<string, mixed> $queryParams
     */
    private function buildController(
        array $filterRows,
        array $facetData,
        array $contentElementRow = ['uid' => 100],
        array $queryParams = [],
        bool $expectFilterRows = true,
    ): StartController&MockObject {
        $this->capturedSearch = null;
        $this->capturedViewVariables = [];

        $index = new Index(SchemaBuilder::DEFAULT_INDEX, [
            'id' => new IdentifierField('id'),
            'site' => new TextField('site', filterable: true),
            'language' => new TextField('language', filterable: true),
            'tags' => new TextField('tags', multiple: true, filterable: true, facet: true),
        ]);
        $schema = new Schema([SchemaBuilder::DEFAULT_INDEX => $index]);

        $searcher = $this->createStub(SearcherInterface::class);
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

        $searchBuilder = new SearchBuilder($schema, $searcher);
        $searchBuilder->index(SchemaBuilder::DEFAULT_INDEX);

        $engine = $this->createStub(EngineInterface::class);
        $engine->method('createSearchBuilder')->willReturn($searchBuilder);

        $seal = $this->createStub(Seal::class);
        $seal->method('buildEngineBySite')->willReturn($engine);
        $seal->method('getIndexNameBySite')->willReturn(SchemaBuilder::DEFAULT_INDEX);

        $subject = $this->getMockBuilder(StartController::class)
            ->setConstructorArgs([new TagConfigurationParser(), new RadiusConfigurationParser(), $seal, new SearchRequestDataResolver()])
            ->onlyMethods(['getFilterRowsByContentElementUid', 'getCurrentContentElementRow', 'redirect'])
            ->getMock();

        $subject->expects(self::once())->method('getCurrentContentElementRow')->willReturn($contentElementRow);
        $subject->expects($expectFilterRows ? self::once() : self::never())
            ->method('getFilterRowsByContentElementUid')
            ->willReturn(new \ArrayIterator($filterRows));

        $site = $this->createStub(Site::class);
        $site->method('getIdentifier')->willReturn('main');

        $siteLanguage = $this->createStub(SiteLanguage::class);
        $siteLanguage->method('getLanguageId')->willReturn(0);

        $serverRequest = (new ServerRequest('https://example.com/', 'GET'))
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('site', $site)
            ->withAttribute('language', $siteLanguage)
            ->withQueryParams($queryParams);

        $reflection = new \ReflectionClass($subject);
        $reflection->getProperty('request')->setValue($subject, new Request($serverRequest));

        $view = $this->createStub(ViewInterface::class);
        $view->method('assignMultiple')
            ->willReturnCallback(function (array $values) use ($view): ViewInterface {
                $this->capturedViewVariables = array_merge($this->capturedViewVariables, $values);
                return $view;
            });
        $view->method('render')->willReturn('');
        $reflection->getProperty('view')->setValue($subject, $view);

        $streamFactory = $this->createStub(StreamFactoryInterface::class);
        $streamFactory->method('createStream')->willReturn($this->createStub(StreamInterface::class));

        $response = $this->createStub(ResponseInterface::class);
        $response->method('withHeader')->willReturn($response);
        $response->method('withBody')->willReturn($response);

        $responseFactory = $this->createStub(ResponseFactoryInterface::class);
        $responseFactory->method('createResponse')->willReturn($response);

        $subject->injectResponseFactory($responseFactory);
        $subject->injectStreamFactory($streamFactory);

        return $subject;
    }
}
