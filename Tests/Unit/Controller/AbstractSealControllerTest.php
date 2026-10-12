<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Controller;

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Schema\Schema;
use CmsIg\Seal\Search\SearchBuilder;
use Doctrine\DBAL\Result;
use Lochmueller\Seal\Controller\AbstractSealController;
use Lochmueller\Seal\Filter\RadiusConfigurationParser;
use Lochmueller\Seal\Filter\TagConfigurationParser;
use Lochmueller\Seal\Seal;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;

class AbstractSealControllerTest extends AbstractTest
{
    public function testBuildSealIdUsesContentElementUid(): void
    {
        self::assertSame('seal-42', $this->buildSubject()->callBuildSealId(42));
    }

    public function testBuildSealIdFallsBackForMissingUid(): void
    {
        self::assertSame('seal-plugin', $this->buildSubject()->callBuildSealId(0));
        self::assertSame('seal-plugin', $this->buildSubject()->callBuildSealId(-1));
    }

    public function testGetCurrentContentElementRowReturnsContentObjectData(): void
    {
        $contentObject = new class {
            /** @var array<string, mixed> */
            public array $data = ['uid' => 12, 'header' => 'Search'];
        };

        $subject = $this->buildSubject();
        $this->setRequest($subject, ['currentContentObject' => $contentObject]);

        self::assertSame(['uid' => 12, 'header' => 'Search'], $subject->callGetCurrentContentElementRow());
    }

    public function testAddCalculatedValuesEnrichesTagConditionRows(): void
    {
        $filterRows = [
            ['uid' => 5, 'type' => 'tagCondition', 'tags' => "page=Seiten\nfile=Dateien"],
        ];

        $result = $this->buildSubject()->callAddCalculatedValuesForFilterRows(
            $filterRows,
            ['field_5' => ['file']],
            ['page' => 7],
        );

        self::assertSame([
            ['value' => 'page', 'label' => 'Seiten', 'count' => 7, 'selected' => false],
            ['value' => 'file', 'label' => 'Dateien', 'count' => 0, 'selected' => true],
        ], $result[0]['parsedTags']);
    }

    public function testAddCalculatedValuesEnrichesGeoDistanceConditionRows(): void
    {
        $filterRows = [
            ['uid' => 6, 'type' => 'geoDistanceCondition', 'radius_steps' => "10=10 km\n50=50 km"],
        ];

        $result = $this->buildSubject()->callAddCalculatedValuesForFilterRows($filterRows);

        self::assertSame([
            ['value' => 10, 'label' => '10 km'],
            ['value' => 50, 'label' => '50 km'],
        ], $result[0]['parsedRadii']);
    }

    public function testAddCalculatedValuesKeepsOtherRowsUntouched(): void
    {
        $filterRows = [
            ['uid' => 1, 'type' => 'searchCondition'],
        ];

        self::assertSame($filterRows, $this->buildSubject()->callAddCalculatedValuesForFilterRows($filterRows));
    }

    public function testAddCalculatedValuesHandlesMissingConfiguration(): void
    {
        $filterRows = [
            ['uid' => 5, 'type' => 'tagCondition'],
            ['uid' => 6, 'type' => 'geoDistanceCondition'],
        ];

        $result = $this->buildSubject()->callAddCalculatedValuesForFilterRows($filterRows);

        self::assertSame([], $result[0]['parsedTags']);
        self::assertSame([], $result[1]['parsedRadii']);
    }

    public function testGetSearchBuilderUsesEngineAndIndexOfSite(): void
    {
        $site = $this->createStub(Site::class);
        $searchBuilder = new SearchBuilder(new Schema([]), $this->createStub(SearcherInterface::class));

        $engine = $this->createMock(EngineInterface::class);
        $engine->expects(self::once())
            ->method('createSearchBuilder')
            ->with('custom-index')
            ->willReturn($searchBuilder);

        $seal = $this->createMock(Seal::class);
        $seal->expects(self::once())->method('buildEngineBySite')->with($site)->willReturn($engine);
        $seal->expects(self::once())->method('getIndexNameBySite')->with($site)->willReturn('custom-index');

        $subject = $this->buildSubject($seal);
        $this->setRequest($subject, ['site' => $site]);

        self::assertSame($searchBuilder, $subject->callGetSearchBuilder());
    }

    public function testGetFilterRowsByContentElementUidQueriesFilterTable(): void
    {
        $rows = [
            ['uid' => 1, 'type' => 'searchCondition'],
            ['uid' => 2, 'type' => 'tagCondition'],
        ];

        $result = $this->createStub(Result::class);
        $result->method('iterateAssociative')->willReturn(new \ArrayIterator($rows));

        $expressionBuilder = $this->createStub(ExpressionBuilder::class);
        $expressionBuilder->method('eq')->willReturn('parent = :dcValue1');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->expects(self::once())->method('createNamedParameter')->with(12)->willReturn(':dcValue1');
        $queryBuilder->expects(self::once())->method('select')->with('*')->willReturnSelf();
        $queryBuilder->expects(self::once())->method('from')->with('tx_seal_domain_model_filter')->willReturnSelf();
        $queryBuilder->expects(self::once())->method('where')->with('parent = :dcValue1')->willReturnSelf();
        $queryBuilder->expects(self::once())->method('orderBy')->with('sorting')->willReturnSelf();
        $queryBuilder->method('executeQuery')->willReturn($result);

        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->expects(self::once())
            ->method('getQueryBuilderForTable')
            ->with('tx_seal_domain_model_filter')
            ->willReturn($queryBuilder);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool);

        self::assertSame($rows, iterator_to_array($this->buildSubject()->callGetFilterRowsByContentElementUid(12)));
    }

    private function buildSubject(?Seal $seal = null): TestableSealController
    {
        return new TestableSealController(
            new TagConfigurationParser(),
            new RadiusConfigurationParser(),
            $seal ?? $this->createStub(Seal::class),
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function setRequest(AbstractSealController $subject, array $attributes): void
    {
        $serverRequest = (new ServerRequest('https://example.com/', 'GET'))
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        foreach ($attributes as $name => $value) {
            $serverRequest = $serverRequest->withAttribute($name, $value);
        }

        (new \ReflectionProperty($subject, 'request'))->setValue($subject, new Request($serverRequest));
    }
}
