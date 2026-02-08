<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Adapter\Typo3;

use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Marshaller\FlattenMarshaller;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\SyncTask;
use CmsIg\Seal\Task\TaskInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Typo3Indexer implements IndexerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private readonly FlattenMarshaller $marshaller;

    public function __construct(private Typo3AdapterHelper $adapterHelper)
    {
        $dateTimeFormat = $this->adapterHelper->getConnection()->getDatabasePlatform()->getDateTimeFormatString();
        $this->marshaller = new FlattenMarshaller(
            dateFormat: $dateTimeFormat,
            fieldSeparator: '_',
        );
    }

    public function save(Index $index, array $document, array $options = []): ?TaskInterface
    {
        $connection = $this->adapterHelper->getConnection();
        $tableName = $this->adapterHelper->getTableName($index);
        $data = $this->marshaller->marshall($index->fields, $document);

        $tags = $data['tags'] ?? [];
        if (isset($data['tags'])) {
            unset($data['tags']);
        }

        try {
            if ($connection->count('*', $tableName, ['id' => $document['id']])) {
                $connection->update($tableName, $data, ['id' => $document['id']]);
            } else {
                $connection->insert($tableName, $data);
            }
        } catch (\Exception $e) {
            $this->logger?->error($e->getMessage());
        }


        if (!empty($tags)) {
            // @todo implement
        }


        if ($options !== []) {
            return new SyncTask($document);
        }

        return null;
    }

    public function delete(Index $index, string $identifier, array $options = []): ?TaskInterface
    {
        $this->adapterHelper->getConnection()->delete($this->adapterHelper->getTableName($index), ['id' => $identifier]);

        if ($options !== []) {
            return new SyncTask(null);
        }

        return null;
    }

    public function bulk(Index $index, iterable $saveDocuments, iterable $deleteDocumentIdentifiers, int $bulkSize = 100, array $options = []): ?TaskInterface
    {
        $connection = $this->adapterHelper->getConnection();
        $tableName = $this->adapterHelper->getTableName($index);

        foreach ($deleteDocumentIdentifiers as $deleteDocumentIdentifier) {
            $connection->delete($tableName, ['id' => $deleteDocumentIdentifier]);
        }

        foreach ($saveDocuments as $saveDocument) {
            $this->save($index, $saveDocument, $options);
        }

        if ($options !== []) {
            return new SyncTask(null);
        }

        return null;
    }
}
