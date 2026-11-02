<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Controller;

use CmsIg\Seal\Search\SearchBuilder;
use Lochmueller\Seal\Filter\Filter;
use Lochmueller\Seal\Seal;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

abstract class AbstractSealController extends ActionController
{
    public function __construct(
        protected readonly Filter $filter,
        private readonly Seal $seal,
    ) {}

    /**
     * @return array<string, mixed>
     */
    protected function getCurrentContentElementRow(): array
    {
        /** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $currentContentObject */
        $currentContentObject = $this->request->getAttribute('currentContentObject');
        return $currentContentObject->data;
    }

    /**
     * Unique, stable prefix for every DOM id that is rendered by the plugin templates.
     *
     * Multiple SEAL plugins on the same page would otherwise produce duplicated ids and
     * break the "for"/"aria-describedby" references of the rendered form controls.
     */
    protected function buildSealId(int $contentElementUid): string
    {
        return 'seal-' . ($contentElementUid > 0 ? (string) $contentElementUid : 'plugin');
    }

    /**
     * @return iterable<array<string, mixed>>
     */
    protected function getFilterRowsByContentElementUid(int $uid): iterable
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_seal_domain_model_filter');

        $where = [
            $queryBuilder->expr()->eq('parent', $queryBuilder->createNamedParameter($uid)),
        ];

        yield from $queryBuilder->select('*')
            ->from('tx_seal_domain_model_filter')
            ->where(...$where)
            ->orderBy('sorting')
            ->executeQuery()
            ->iterateAssociative();
    }

    /**
     * The controller stays free of any knowledge about concrete filter types: every filter
     * adds the values its own partial needs.
     *
     * @param array<int, array<string, mixed>> $filterRows
     * @param array<string, mixed> $requestData
     * @param array<string, int> $tagFacetCounts
     * @return array<int, array<string, mixed>>
     */
    protected function addCalculatedValuesForFilterRows(array $filterRows, array $requestData = [], array $tagFacetCounts = []): array
    {
        return $this->filter->enrichFilterRows($filterRows, $requestData, $tagFacetCounts);
    }

    protected function getSearchBuilder(): SearchBuilder
    {
        /** @var Site $site */
        $site = $this->request->getAttribute('site');
        $engine = $this->seal->buildEngineBySite($site);
        return $engine->createSearchBuilder($this->seal->getIndexNameBySite($site));
    }

}
