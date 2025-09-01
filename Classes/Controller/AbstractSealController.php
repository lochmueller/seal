<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Controller;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

abstract class AbstractSealController extends ActionController
{
    protected function getCurrentContentElementRow(): array
    {
        /** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $currentContentObject */
        $currentContentObject = $this->request->getAttribute('currentContentObject');
        return $currentContentObject->data;
    }

    /**
     * @return iterable<array>
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

}
