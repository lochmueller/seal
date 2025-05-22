<?php

declare(strict_types=1);

namespace Seal\Adapter;

use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\TaskInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\SchemaInformation;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Typo3SchemaManager implements SchemaManagerInterface
{
    public function existIndex(Index $index): bool
    {
        /** @var Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);

        // via
        # -> SchemaInformation::class
        # Check if tablename exists


        // Check AlterTableDefinitionStatementsEvent

        # $index->name
        // TODO: Implement existIndex() method.
    }

    public function dropIndex(Index $index, array $options = []): TaskInterface|null
    {
        // TODO: Implement dropIndex() method.
    }

    public function createIndex(Index $index, array $options = []): TaskInterface|null
    {
        // TODO: Implement createIndex() method.
    }
}
