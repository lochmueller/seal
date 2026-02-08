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
use TYPO3\CMS\Core\Database\Connection;

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


        $this->syncTags($tableName, $document['id'], $tags);


        if ($options !== []) {
            return new SyncTask($document);
        }

        return null;
    }

    /**
     * @param string $tableName
     * @param string $documentId
     * @param array<int, string> $tags
     */
    private function syncTags(string $tableName, string $documentId, array $tags): void
    {
        $connection = $this->adapterHelper->getConnection();
        $tagTable = $tableName . '_tag';
        $mmTable = $tableName . '_mm_tag';

        // Resolve uid_local from the document id
        $row = $connection->select(['uid'], $tableName, ['id' => $documentId])->fetchAssociative();
        if ($row === false) {
            return;
        }
        $uidLocal = (int) $row['uid'];

        // Remove existing MM relations for this document
        $connection->delete($mmTable, ['uid_local' => $uidLocal]);

        if ($tags === []) {
            $connection->update($tableName, ['tags' => 0], ['uid' => $uidLocal]);
            return;
        }

        $sorting = 0;
        foreach ($tags as $tagValue) {
            $tagUid = $this->resolveOrCreateTag($connection, $tagTable, (string) $tagValue);

            $connection->insert($mmTable, [
                'uid_local' => $uidLocal,
                'uid_foreign' => $tagUid,
                'sorting' => $sorting,
                'sorting_foreign' => $sorting,
            ]);
            ++$sorting;
        }

        // Update the tag count on the parent record (TYPO3 MM convention)
        $connection->update($tableName, ['tags' => count($tags)], ['uid' => $uidLocal]);
    }

    private function resolveOrCreateTag(Connection $connection, string $tagTable, string $tagValue): int
    {
        $existing = $connection->select(['uid'], $tagTable, ['title' => $tagValue])->fetchAssociative();
        if ($existing !== false) {
            return (int) $existing['uid'];
        }

        $connection->insert($tagTable, [
            'title' => $tagValue,
            'crdate' => time(),
            'tstamp' => time(),
        ]);

        return (int) $connection->lastInsertId();
    }


    public function delete(Index $index, string $identifier, array $options = []): ?TaskInterface
    {
        $connection = $this->adapterHelper->getConnection();
        $tableName = $this->adapterHelper->getTableName($index);
        $mmTable = $tableName . '_mm_tag';

        // Remove MM relations before deleting the document
        $row = $connection->select(['uid'], $tableName, ['id' => $identifier])->fetchAssociative();
        if ($row !== false) {
            $connection->delete($mmTable, ['uid_local' => (int) $row['uid']]);
        }

        $connection->delete($tableName, ['id' => $identifier]);

        if ($options !== []) {
            return new SyncTask(null);
        }

        return null;
    }

    public function bulk(Index $index, iterable $saveDocuments, iterable $deleteDocumentIdentifiers, int $bulkSize = 100, array $options = []): ?TaskInterface
    {
        foreach ($deleteDocumentIdentifiers as $deleteDocumentIdentifier) {
            $this->delete($index, $deleteDocumentIdentifier, $options);
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
