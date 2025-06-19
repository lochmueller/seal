<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Adapter;

use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Marshaller\Marshaller;
use CmsIg\Seal\Schema\Index;
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
        return null;
    }

    public function delete(Index $index, string $identifier, array $options = []): TaskInterface|null
    {
        $this->adapterHelper->getConnection()->delete($this->adapterHelper->getTableName($index), ['id' => $identifier]);
        return null;
    }

    public function bulk(Index $index, iterable $saveDocuments, iterable $deleteDocumentIdentifiers, int $bulkSize = 100, array $options = []): TaskInterface|null
    {
        // TODO: Implement bulk() method.
    }
}
