<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;

class StatRepository
{
    private const TABLE_NAME = 'tx_seal_domain_model_stat';

    /**
     * Length of the "search_term" column (varchar(255) in ext_tables.sql).
     */
    private const MAX_SEARCH_TERM_LENGTH = 255;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * Stores a search query in the statistics table.
     * Empty search terms (whitespace only) are ignored. Search terms that exceed the column
     * length are truncated, otherwise the insert fails in strict mode.
     */
    public function logSearchQuery(string $searchTerm, string $siteIdentifier, int $languageId): void
    {
        $searchTerm = trim($searchTerm);
        if ($searchTerm === '') {
            return;
        }

        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_NAME);
        $connection->insert(self::TABLE_NAME, [
            'search_term' => mb_substr($searchTerm, 0, self::MAX_SEARCH_TERM_LENGTH),
            'site' => $siteIdentifier,
            'language' => (string) $languageId,
            'crdate' => time(),
            'tstamp' => time(),
        ]);
    }

    /**
     * Returns the latest $limit search queries, ordered by crdate descending.
     *
     * @return list<array<string, mixed>>
     */
    public function findLatest(int $limit = 20): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);

        return $queryBuilder
            ->select('search_term', 'site', 'language', 'crdate')
            ->from(self::TABLE_NAME)
            ->orderBy('crdate', 'DESC')
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Returns the $limit most frequent search terms of the current calendar month.
     *
     * @return list<array<string, mixed>>
     */
    public function findTopSearchesOfCurrentMonth(int $limit = 10): array
    {
        $firstDayOfMonth = (new \DateTimeImmutable('first day of this month 00:00:00'))->getTimestamp();

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);

        return $queryBuilder
            ->select('search_term')
            ->addSelectLiteral('COUNT(*) AS count')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->gte('crdate', $queryBuilder->createNamedParameter($firstDayOfMonth, \Doctrine\DBAL\ParameterType::INTEGER))
            )
            ->groupBy('search_term')
            ->orderBy('count', 'DESC')
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
