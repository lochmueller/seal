<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Adapter\Dbal;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Adapter\SearcherInterface;

readonly class DbalAdapter implements AdapterInterface
{
    public function __construct(
        private DbalSchemaManager $schemaManager,
        private DbalIndexer       $indexer,
        private DbalSearcher      $searcher,
    ) {}

    public function getSchemaManager(): SchemaManagerInterface
    {
        return $this->schemaManager;
    }

    public function getIndexer(): IndexerInterface
    {
        return $this->indexer;
    }

    public function getSearcher(): SearcherInterface
    {
        return $this->searcher;
    }
}
