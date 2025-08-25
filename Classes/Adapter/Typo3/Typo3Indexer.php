<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Adapter\Typo3;

use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Marshaller\FlattenMarshaller;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\SyncTask;
use CmsIg\Seal\Task\TaskInterface;

class Typo3Indexer implements IndexerInterface
{
    private readonly FlattenMarshaller $marshaller;

    public function __construct(private Typo3AdapterHelper $adapterHelper)
    {
        $this->marshaller = new FlattenMarshaller(fieldSeparator: '_');
    }

    public function save(Index $index, array $document, array $options = []): TaskInterface|null
    {
        $connection = $this->adapterHelper->getConnection();
        $tableName = $this->adapterHelper->getTableName($index);
        $data = $this->marshaller->marshall($index->fields, $document);

        $tags = $data['tags'] ?? [];
        if (isset($data['tags'])) {
            unset($data['tags']);
        }

        if ($connection->count('*', $tableName, ['id' => $document['id']])) {
            $connection->update($tableName, ['id' => $document['id']], $data);
        } else {
            $connection->insert($tableName, $data);
        }


        if (!empty($tags)) {
            // @todo implement
        }


        return new SyncTask(null);
    }

    public function delete(Index $index, string $identifier, array $options = []): TaskInterface|null
    {
        $this->adapterHelper->getConnection()->delete($this->adapterHelper->getTableName($index), ['id' => $identifier]);

        return new SyncTask(null);
    }

    public function bulk(Index $index, iterable $saveDocuments, iterable $deleteDocumentIdentifiers, int $bulkSize = 100, array $options = []): TaskInterface|null
    {
        $connection = $this->adapterHelper->getConnection();
        $tableName = $this->adapterHelper->getTableName($index);

        foreach ($deleteDocumentIdentifiers as $deleteDocumentIdentifier) {
            $connection->delete($tableName, ['id' => $deleteDocumentIdentifier]);
        }

        $bulk = [];
        foreach ($saveDocuments as $saveDocument) {
            $bulk[] = $saveDocument;
            if (sizeof($bulk) >= $bulkSize) {
                $connection->bulkInsert($tableName, $bulk);
                $bulk = [];
            }
        }

        if (!empty($bulk)) {
            $connection->bulkInsert($tableName, $bulk);
        }

        return new SyncTask(null);
    }
}
