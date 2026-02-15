<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Adapter\Typo3;

use CmsIg\Seal\Schema\Field\IdentifierField;
use CmsIg\Seal\Schema\Field\TextField;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Search\Condition;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\Search;
use Doctrine\DBAL\Result as DoctrineResult;
use Lochmueller\Seal\Adapter\Typo3\Typo3AdapterHelper;
use Lochmueller\Seal\Adapter\Typo3\Typo3Searcher;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

class Typo3SearcherTest extends AbstractTest
{
    private Index $index;

    private Typo3Searcher $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->createStub(Connection::class);
        $connection->method('quote')->willReturnCallback(
            static fn(string $value): string => "'" . addslashes($value) . "'",
        );

        $adapterHelper = $this->createStub(Typo3AdapterHelper::class);
        $adapterHelper->method('getConnection')->willReturn($connection);
        $adapterHelper->method('getTableName')->willReturn('tx_seal_domain_model_index_default');

        $this->index = new Index('default', [
            'id' => new IdentifierField('id'),
            'title' => new TextField('title'),
            'content' => new TextField('content'),
        ]);

        $this->subject = new Typo3Searcher($adapterHelper);
    }

    private function createQueryBuilderStub(): QueryBuilder
    {
        $expressionBuilder = $this->createStub(ExpressionBuilder::class);
        $expressionBuilder->method('eq')->willReturn('field = value');
        $expressionBuilder->method('neq')->willReturn('field != value');
        $expressionBuilder->method('gt')->willReturn('field > value');
        $expressionBuilder->method('gte')->willReturn('field >= value');
        $expressionBuilder->method('lt')->willReturn('field < value');
        $expressionBuilder->method('lte')->willReturn('field <= value');
        $expressionBuilder->method('in')->willReturn('field IN (values)');
        $expressionBuilder->method('notIn')->willReturn('field NOT IN (values)');
        $expressionBuilder->method('like')->willReturn('field LIKE value');
        $expressionBuilder->method('literal')->willReturnCallback(
            static fn(string $input): string => "'" . $input . "'",
        );
        $expressionBuilder->method('and')->willReturn(CompositeExpression::and('1=1'));
        $expressionBuilder->method('or')->willReturn(CompositeExpression::or('1=1'));

        $doctrineResult = $this->createStub(DoctrineResult::class);
        $doctrineResult->method('fetchAssociative')->willReturn(['COUNT(*)' => 0]);
        $doctrineResult->method('iterateAssociative')->willReturn(new \EmptyIterator());

        $queryBuilder = $this->createStub(QueryBuilder::class);
        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('count')->willReturnSelf();
        $queryBuilder->method('setFirstResult')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('addOrderBy')->willReturnSelf();
        $queryBuilder->method('executeQuery')->willReturn($doctrineResult);

        return $queryBuilder;
    }

    public function testSearchReturnsResult(): void
    {
        $adapterHelper = $this->createStub(Typo3AdapterHelper::class);
        $adapterHelper->method('getConnection')->willReturn($this->createStub(Connection::class));
        $adapterHelper->method('getTableName')->willReturn('tx_seal_domain_model_index_default');
        $adapterHelper->method('getQueryBuilder')->willReturn($this->createQueryBuilderStub());

        $subject = new Typo3Searcher($adapterHelper);
        $search = new Search($this->index);
        $result = $subject->search($search);

        self::assertInstanceOf(Result::class, $result);
        self::assertSame(0, $result->total());
    }

    public function testSearchWithLimitAndOffset(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $expressionBuilder = $this->createStub(ExpressionBuilder::class);
        $expressionBuilder->method('and')->willReturn(CompositeExpression::and('1=1'));

        $doctrineResult = $this->createStub(DoctrineResult::class);
        $doctrineResult->method('fetchAssociative')->willReturn(['COUNT(*)' => 0]);
        $doctrineResult->method('iterateAssociative')->willReturn(new \EmptyIterator());

        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('count')->willReturnSelf();
        $queryBuilder->method('executeQuery')->willReturn($doctrineResult);
        $queryBuilder->expects(self::atLeastOnce())->method('setMaxResults')->with(10)->willReturnSelf();
        $queryBuilder->expects(self::atLeastOnce())->method('setFirstResult')->with(20)->willReturnSelf();

        $adapterHelper = $this->createStub(Typo3AdapterHelper::class);
        $adapterHelper->method('getConnection')->willReturn($this->createStub(Connection::class));
        $adapterHelper->method('getTableName')->willReturn('tx_seal_domain_model_index_default');
        $adapterHelper->method('getQueryBuilder')->willReturn($queryBuilder);

        $subject = new Typo3Searcher($adapterHelper);
        $search = new Search($this->index, limit: 10, offset: 20);
        $result = $subject->search($search);

        self::assertInstanceOf(Result::class, $result);
    }

    public function testSearchWithIdentifierCondition(): void
    {
        $adapterHelper = $this->createStub(Typo3AdapterHelper::class);
        $adapterHelper->method('getConnection')->willReturn($this->createStub(Connection::class));
        $adapterHelper->method('getTableName')->willReturn('tx_seal_domain_model_index_default');
        $adapterHelper->method('getQueryBuilder')->willReturn($this->createQueryBuilderStub());

        $subject = new Typo3Searcher($adapterHelper);
        $search = new Search($this->index, filters: [
            new Condition\IdentifierCondition('doc-1'),
        ]);

        $result = $subject->search($search);

        self::assertInstanceOf(Result::class, $result);
    }

    public function testSearchWithSearchCondition(): void
    {
        $adapterHelper = $this->createStub(Typo3AdapterHelper::class);
        $adapterHelper->method('getConnection')->willReturn($this->createStub(Connection::class));
        $adapterHelper->method('getTableName')->willReturn('tx_seal_domain_model_index_default');
        $adapterHelper->method('getQueryBuilder')->willReturn($this->createQueryBuilderStub());

        $subject = new Typo3Searcher($adapterHelper);
        $search = new Search($this->index, filters: [
            new Condition\SearchCondition('hello world'),
        ]);

        $result = $subject->search($search);

        self::assertInstanceOf(Result::class, $result);
    }

    public function testSearchWithEqualCondition(): void
    {
        $adapterHelper = $this->createStub(Typo3AdapterHelper::class);
        $adapterHelper->method('getConnection')->willReturn($this->createStub(Connection::class));
        $adapterHelper->method('getTableName')->willReturn('tx_seal_domain_model_index_default');
        $adapterHelper->method('getQueryBuilder')->willReturn($this->createQueryBuilderStub());

        $subject = new Typo3Searcher($adapterHelper);
        $search = new Search($this->index, filters: [
            new Condition\EqualCondition('title', 'Test'),
        ]);

        $result = $subject->search($search);

        self::assertInstanceOf(Result::class, $result);
    }

    public function testSearchWithAndCondition(): void
    {
        $adapterHelper = $this->createStub(Typo3AdapterHelper::class);
        $adapterHelper->method('getConnection')->willReturn($this->createStub(Connection::class));
        $adapterHelper->method('getTableName')->willReturn('tx_seal_domain_model_index_default');
        $adapterHelper->method('getQueryBuilder')->willReturn($this->createQueryBuilderStub());

        $subject = new Typo3Searcher($adapterHelper);
        $search = new Search($this->index, filters: [
            new Condition\AndCondition(
                new Condition\EqualCondition('title', 'A'),
                new Condition\EqualCondition('content', 'B'),
            ),
        ]);

        $result = $subject->search($search);

        self::assertInstanceOf(Result::class, $result);
    }

    public function testSearchWithOrCondition(): void
    {
        $adapterHelper = $this->createStub(Typo3AdapterHelper::class);
        $adapterHelper->method('getConnection')->willReturn($this->createStub(Connection::class));
        $adapterHelper->method('getTableName')->willReturn('tx_seal_domain_model_index_default');
        $adapterHelper->method('getQueryBuilder')->willReturn($this->createQueryBuilderStub());

        $subject = new Typo3Searcher($adapterHelper);
        $search = new Search($this->index, filters: [
            new Condition\OrCondition(
                new Condition\EqualCondition('title', 'A'),
                new Condition\EqualCondition('title', 'B'),
            ),
        ]);

        $result = $subject->search($search);

        self::assertInstanceOf(Result::class, $result);
    }

    public function testSearchWithSortBy(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $expressionBuilder = $this->createStub(ExpressionBuilder::class);
        $expressionBuilder->method('and')->willReturn(CompositeExpression::and('1=1'));

        $doctrineResult = $this->createStub(DoctrineResult::class);
        $doctrineResult->method('fetchAssociative')->willReturn(['COUNT(*)' => 0]);
        $doctrineResult->method('iterateAssociative')->willReturn(new \EmptyIterator());

        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('count')->willReturnSelf();
        $queryBuilder->method('setFirstResult')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('executeQuery')->willReturn($doctrineResult);
        $queryBuilder->expects(self::once())->method('addOrderBy')->with('title', 'asc')->willReturnSelf();

        $adapterHelper = $this->createStub(Typo3AdapterHelper::class);
        $adapterHelper->method('getConnection')->willReturn($this->createStub(Connection::class));
        $adapterHelper->method('getTableName')->willReturn('tx_seal_domain_model_index_default');
        $adapterHelper->method('getQueryBuilder')->willReturn($queryBuilder);

        $subject = new Typo3Searcher($adapterHelper);
        $search = new Search($this->index, sortBys: ['title' => 'asc']);
        $result = $subject->search($search);

        self::assertInstanceOf(Result::class, $result);
    }

    public function testSearchThrowsOnUnsupportedCondition(): void
    {
        $adapterHelper = $this->createStub(Typo3AdapterHelper::class);
        $adapterHelper->method('getConnection')->willReturn($this->createStub(Connection::class));
        $adapterHelper->method('getTableName')->willReturn('tx_seal_domain_model_index_default');
        $adapterHelper->method('getQueryBuilder')->willReturn($this->createQueryBuilderStub());

        $subject = new Typo3Searcher($adapterHelper);

        $unsupportedFilter = new class {};

        $search = new Search($this->index, filters: [$unsupportedFilter]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/Unsupported filter condition type/');

        $subject->search($search);
    }

    public function testCountReturnsInteger(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('count')->willReturn(42);

        $adapterHelper = $this->createStub(Typo3AdapterHelper::class);
        $adapterHelper->method('getConnection')->willReturn($connection);
        $adapterHelper->method('getTableName')->willReturn('tx_seal_domain_model_index_default');

        $subject = new Typo3Searcher($adapterHelper);

        self::assertSame(42, $subject->count($this->index));
    }

    public function testCountReturnsZeroForEmptyIndex(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('count')->willReturn(0);

        $adapterHelper = $this->createStub(Typo3AdapterHelper::class);
        $adapterHelper->method('getConnection')->willReturn($connection);
        $adapterHelper->method('getTableName')->willReturn('tx_seal_domain_model_index_default');

        $subject = new Typo3Searcher($adapterHelper);

        self::assertSame(0, $subject->count($this->index));
    }

    public function testSearchWithComparisonConditions(): void
    {
        $adapterHelper = $this->createStub(Typo3AdapterHelper::class);
        $adapterHelper->method('getConnection')->willReturn($this->createStub(Connection::class));
        $adapterHelper->method('getTableName')->willReturn('tx_seal_domain_model_index_default');
        $adapterHelper->method('getQueryBuilder')->willReturn($this->createQueryBuilderStub());

        $subject = new Typo3Searcher($adapterHelper);
        $search = new Search($this->index, filters: [
            new Condition\GreaterThanCondition('title', 'A'),
            new Condition\GreaterThanEqualCondition('title', 'B'),
            new Condition\LessThanCondition('title', 'Z'),
            new Condition\LessThanEqualCondition('title', 'Y'),
            new Condition\NotEqualCondition('title', 'X'),
            new Condition\InCondition('title', ['A', 'B']),
            new Condition\NotInCondition('title', ['C', 'D']),
        ]);

        $result = $subject->search($search);

        self::assertInstanceOf(Result::class, $result);
    }

    public function testSearchWithDocumentsReturnsUnmarshalledData(): void
    {
        $expressionBuilder = $this->createStub(ExpressionBuilder::class);
        $expressionBuilder->method('and')->willReturn(CompositeExpression::and('1=1'));

        $countResult = $this->createStub(DoctrineResult::class);
        $countResult->method('fetchAssociative')->willReturn(['COUNT(*)' => 1]);

        $hitsResult = $this->createStub(DoctrineResult::class);
        $hitsResult->method('iterateAssociative')->willReturn(new \ArrayIterator([
            ['id' => 'doc-1', 'title' => 'Hello', 'content' => 'World'],
        ]));

        $callCount = 0;
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('count')->willReturnSelf();
        $queryBuilder->method('setFirstResult')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('executeQuery')->willReturnCallback(
            function () use (&$callCount, $countResult, $hitsResult): DoctrineResult {
                return ++$callCount <= 1 ? $countResult : $hitsResult;
            },
        );

        $adapterHelper = $this->createStub(Typo3AdapterHelper::class);
        $adapterHelper->method('getConnection')->willReturn($this->createStub(Connection::class));
        $adapterHelper->method('getTableName')->willReturn('tx_seal_domain_model_index_default');
        $adapterHelper->method('getQueryBuilder')->willReturn($queryBuilder);

        $subject = new Typo3Searcher($adapterHelper);
        $search = new Search($this->index);
        $result = $subject->search($search);

        self::assertSame(1, $result->total());

        $documents = iterator_to_array($result);
        self::assertCount(1, $documents);
        self::assertSame('doc-1', $documents[0]['id']);
    }
}
