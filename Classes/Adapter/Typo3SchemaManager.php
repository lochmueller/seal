<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Adapter;

use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\TaskInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Typo3SchemaManager implements SchemaManagerInterface
{
    public function existIndex(Index $index): bool
    {
        return in_array($this->getTableName($index), $this->getConnection()->getSchemaInformation()->listTableNames(), true);
    }

    public function dropIndex(Index $index, array $options = []): TaskInterface|null
    {
        if (!$this->existIndex($index)) {
            throw new \Exception('Please create the database structure with TYPO3 TCA management and ext_tables.sql handling for table: ' . $this->getTableName($index), 1238123);
        }

        $this->getConnection()->truncate($this->getTableName($index));
        return null;
    }

    public function createIndex(Index $index, array $options = []): TaskInterface|null
    {
        // Nothing to do...
        // All index tables should exist via DB compare
        return null;
    }

    protected function getConnection(): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
    }

    protected function getTableName(Index $index): string
    {
        return 'tx_seal_domain_model_index_' . $index->name;
    }
}
