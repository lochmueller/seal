<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Adapter;

use CmsIg\Seal\Schema\Index;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Typo3AdapterHelper
{
    public function getConnection(): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
    }

    public function getTableName(Index $index): string
    {
        return 'tx_seal_domain_model_index_' . $index->name;
    }

}
