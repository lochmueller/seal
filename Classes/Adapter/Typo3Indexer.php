<?php

namespace Seal\Adapter;

use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Marshaller\Marshaller;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\TaskInterface;

class Typo3Indexer implements IndexerInterface
{

    private readonly Marshaller $marshaller;
    public function __construct()
    {
        $this->marshaller = new Marshaller();
    }

    public function save(Index $index, array $document, array $options = []): TaskInterface|null
    {
        // TODO: Implement save() method.
    }

    public function delete(Index $index, string $identifier, array $options = []): TaskInterface|null
    {
        // TODO: Implement delete() method.
    }

    public function bulk(Index $index, iterable $saveDocuments, iterable $deleteDocumentIdentifiers, int $bulkSize = 100, array $options = [],): TaskInterface|null
    {
        // TODO: Implement bulk() method.
    }
}
