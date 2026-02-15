<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Adapter\Typo3;

use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Adapter\SearcherInterface;
use Lochmueller\Seal\Adapter\Typo3\Typo3Adapter;
use Lochmueller\Seal\Adapter\Typo3\Typo3Indexer;
use Lochmueller\Seal\Adapter\Typo3\Typo3SchemaManager;
use Lochmueller\Seal\Adapter\Typo3\Typo3Searcher;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

class Typo3AdapterTest extends AbstractTest
{
    private Typo3Adapter $subject;

    private Typo3SchemaManager $schemaManager;

    private Typo3Indexer $indexer;

    private Typo3Searcher $searcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schemaManager = $this->createStub(Typo3SchemaManager::class);
        $this->indexer = $this->createStub(Typo3Indexer::class);
        $this->searcher = $this->createStub(Typo3Searcher::class);

        $this->subject = new Typo3Adapter(
            $this->schemaManager,
            $this->indexer,
            $this->searcher,
        );
    }

    public function testGetSchemaManagerReturnsSchemaManagerInterface(): void
    {
        $result = $this->subject->getSchemaManager();

        self::assertInstanceOf(SchemaManagerInterface::class, $result);
        self::assertSame($this->schemaManager, $result);
    }

    public function testGetIndexerReturnsIndexerInterface(): void
    {
        $result = $this->subject->getIndexer();

        self::assertInstanceOf(IndexerInterface::class, $result);
        self::assertSame($this->indexer, $result);
    }

    public function testGetSearcherReturnsSearcherInterface(): void
    {
        $result = $this->subject->getSearcher();

        self::assertInstanceOf(SearcherInterface::class, $result);
        self::assertSame($this->searcher, $result);
    }
}
