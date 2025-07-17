<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Adapter\Typo3;

use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Marshaller\Marshaller;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\SyncTask;
use CmsIg\Seal\Task\TaskInterface;

class Typo3Indexer implements IndexerInterface
{
    private readonly Marshaller $marshaller;

    public function __construct(private Typo3AdapterHelper $adapterHelper)
    {
        $this->marshaller = new Marshaller();
    }

    public function save(Index $index, array $document, array $options = []): TaskInterface|null
    {
        $data = $this->marshaller->marshall($index->fields, $document);
        $this->adapterHelper->getConnection()->insert($this->adapterHelper->getTableName($index), $data);

        return new SyncTask(null);
    }

    public function delete(Index $index, string $identifier, array $options = []): TaskInterface|null
    {
        $this->adapterHelper->getConnection()->delete($this->adapterHelper->getTableName($index), ['id' => $identifier]);

        return new SyncTask(null);
    }

    public function bulk(Index $index, iterable $saveDocuments, iterable $deleteDocumentIdentifiers, int $bulkSize = 100, array $options = []): TaskInterface|null
    {
        // @todo optimize
        $connection = $this->adapterHelper->getConnection();

        foreach ($deleteDocumentIdentifiers as $deleteDocumentIdentifier) {
            $connection->delete($this->adapterHelper->getTableName($index), ['id' => $deleteDocumentIdentifier]);
        }

        $bulk = [];
        foreach ($saveDocuments as $saveDocument) {
            $bulk[] = $saveDocument;
            if (sizeof($bulk) >= $bulkSize) {
                $connection->bulkInsert($this->adapterHelper->getTableName($index), $bulk);
                $bulk = [];
            }
        }

        if (!empty($bulk)) {
            $connection->bulkInsert($this->adapterHelper->getTableName($index), $bulk);
        }

        return new SyncTask(null);
    }
}
