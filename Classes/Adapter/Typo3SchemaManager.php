<?php

namespace Seal\Adapter;

use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\TaskInterface;

class Typo3SchemaManager implements SchemaManagerInterface
{

    public function existIndex(Index $index): bool
    {
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
