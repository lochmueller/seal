<?php

namespace Seal\Adapter;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Adapter\SearcherInterface;

class Typo3Adapter implements AdapterInterface
{
    public function __construct(
        private ?Typo3SchemaManager $schemaManager = null,
        private ?Typo3Indexer       $indexer = null,
        private ?Typo3Searcher      $searcher = null,
    )
    {
        $this->schemaManager = $schemaManager ?? new Typo3SchemaManager();
        $this->indexer = $indexer ?? new Typo3Indexer();
        $this->searcher = $searcher ?? new Typo3Searcher();
    }

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
