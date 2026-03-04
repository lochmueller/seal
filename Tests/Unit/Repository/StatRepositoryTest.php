<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Repository;

use Doctrine\DBAL\Result;
use Lochmueller\Seal\Repository\StatRepository;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

class StatRepositoryTest extends AbstractTest
{
    public function testLogSearchQueryIgnoresEmptyString(): void
    {
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->expects(self::never())->method('getConnectionForTable');

        $subject = new StatRepository($connectionPool);
        $subject->logSearchQuery('', 'main', 0);
    }

    public function testLogSearchQueryIgnoresWhitespaceOnlyString(): void
    {
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->expects(self::never())->method('getConnectionForTable');

        $subject = new StatRepository($connectionPool);
        $subject->logSearchQuery('   ', 'main', 0);
    }

    public function testLogSearchQueryInsertsWithCorrectParameters(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('insert')
            ->with(
                'tx_seal_domain_model_stat',
                self::callback(static fn(array $data): bool => $data['search_term'] === 'test query'
                        && $data['site'] === 'portal'
                        && $data['language'] === '2'
                        && isset($data['crdate'])
                        && isset($data['tstamp'])),
            );

        $connectionPool = $this->createStub(ConnectionPool::class);
        $connectionPool->method('getConnectionForTable')->willReturn($connection);

        $subject = new StatRepository($connectionPool);
        $subject->logSearchQuery('test query', 'portal', 2);
    }

    public function testLogSearchQueryTrimsSearchTerm(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('insert')
            ->with(
                'tx_seal_domain_model_stat',
                self::callback(static fn(array $data): bool => $data['search_term'] === 'trimmed'),
            );

        $connectionPool = $this->createStub(ConnectionPool::class);
        $connectionPool->method('getConnectionForTable')->willReturn($connection);

        $subject = new StatRepository($connectionPool);
        $subject->logSearchQuery('  trimmed  ', 'main', 0);
    }

    public function testFindLatestReturnsCorrectlySortedResults(): void
    {
        $expectedRows = [
            ['search_term' => 'newest', 'site' => 'main', 'language' => '0', 'crdate' => 1000002],
            ['search_term' => 'older', 'site' => 'main', 'language' => '0', 'crdate' => 1000001],
        ];

        $result = $this->createStub(Result::class);
        $result->method('fetchAllAssociative')->willReturn($expectedRows);

        $queryBuilder = $this->createStub(QueryBuilder::class);
        $queryBuilder->method('select')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('orderBy')->willReturn($queryBuilder);
        $queryBuilder->method('setMaxResults')->willReturn($queryBuilder);
        $queryBuilder->method('executeQuery')->willReturn($result);

        $connectionPool = $this->createStub(ConnectionPool::class);
        $connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);

        $subject = new StatRepository($connectionPool);
        $rows = $subject->findLatest();

        self::assertSame($expectedRows, $rows);
    }

    public function testFindTopSearchesOfCurrentMonthGroupsAndSortsByCount(): void
    {
        $expectedRows = [
            ['search_term' => 'popular', 'count' => 42],
            ['search_term' => 'less popular', 'count' => 10],
        ];

        $result = $this->createStub(Result::class);
        $result->method('fetchAllAssociative')->willReturn($expectedRows);

        $expressionBuilder = $this->createStub(ExpressionBuilder::class);
        $expressionBuilder->method('gte')->willReturn('crdate >= :param');

        $queryBuilder = $this->createStub(QueryBuilder::class);
        $queryBuilder->method('select')->willReturn($queryBuilder);
        $queryBuilder->method('addSelectLiteral')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('groupBy')->willReturn($queryBuilder);
        $queryBuilder->method('orderBy')->willReturn($queryBuilder);
        $queryBuilder->method('setMaxResults')->willReturn($queryBuilder);
        $queryBuilder->method('executeQuery')->willReturn($result);
        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->method('createNamedParameter')->willReturn(':param');

        $connectionPool = $this->createStub(ConnectionPool::class);
        $connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);

        $subject = new StatRepository($connectionPool);
        $rows = $subject->findTopSearchesOfCurrentMonth();

        self::assertSame($expectedRows, $rows);
    }
}
