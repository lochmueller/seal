<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;

class StatRepository
{
    private const TABLE_NAME = 'tx_seal_domain_model_stat';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * Speichert eine Suchanfrage in der Statistiktabelle.
     * Ignoriert leere Suchbegriffe (nur Whitespace).
     */
    public function logSearchQuery(string $searchTerm, string $siteIdentifier, int $languageId): void
    {
        $searchTerm = trim($searchTerm);
        if ($searchTerm === '') {
            return;
        }

        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_NAME);
        $connection->insert(self::TABLE_NAME, [
            'search_term' => $searchTerm,
            'site' => $siteIdentifier,
            'language' => (string) $languageId,
            'crdate' => time(),
            'tstamp' => time(),
        ]);
    }

    /**
     * Liefert die neuesten $limit Suchanfragen, absteigend nach crdate.
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
     * Liefert die $limit häufigsten Suchbegriffe des aktuellen Kalendermonats.
     *
     * @return list<array<string, mixed>>
     */
    public function findTopSearchesOfCurrentMonth(int $limit = 10): array
    {
        $firstDayOfMonth = (int) strtotime('first day of this month midnight');

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
