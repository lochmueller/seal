<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Adapter\Dbal;

use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\TaskInterface;
use Lochmueller\Seal\Adapter\AdapterHelper;

class DbalSchemaManager implements SchemaManagerInterface
{
    public function __construct(private AdapterHelper $adapterHelper) {}

    public function existIndex(Index $index): bool
    {
        return in_array($this->adapterHelper->getTableName($index), $this->adapterHelper->getConnection()->getSchemaInformation()->listTableNames(), true);
    }

    public function dropIndex(Index $index, array $options = []): TaskInterface|null
    {
        if (!$this->existIndex($index)) {
            throw new \Exception('Please create the database structure with TYPO3 TCA management and ext_tables.sql handling for table: ' . $this->adapterHelper->getTableName($index), 1238123);
        }

        $this->adapterHelper->getConnection()->truncate($this->adapterHelper->getTableName($index));
        return null;
    }

    public function createIndex(Index $index, array $options = []): TaskInterface|null
    {
        // Nothing to do...
        // All index tables should exist via DB compare
        return null;
    }
}
