<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Adapter\Typo3;

use CmsIg\Seal\Schema\Field\IdentifierField;
use CmsIg\Seal\Schema\Field\TextField;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\SyncTask;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;
use Lochmueller\Seal\Adapter\Typo3\Typo3AdapterHelper;
use Lochmueller\Seal\Adapter\Typo3\Typo3Indexer;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\Connection;

class Typo3IndexerTest extends AbstractTest
{
    private Connection $connection;

    private Index $index;

    private Typo3Indexer $subject;

    private function createSubjectWithConnection(Connection $connection): Typo3Indexer
    {
        $platform = $this->createStub(AbstractPlatform::class);
        $platform->method('getDateTimeFormatString')->willReturn('Y-m-d H:i:s');

        $connection->method('getDatabasePlatform')->willReturn($platform);

        $adapterHelper = $this->createStub(Typo3AdapterHelper::class);
        $adapterHelper->method('getConnection')->willReturn($connection);
        $adapterHelper->method('getTableName')->willReturn('tx_seal_domain_model_index_default');

        return new Typo3Indexer($adapterHelper);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->index = new Index('default', [
            'id' => new IdentifierField('id'),
            'title' => new TextField('title'),
            'content' => new TextField('content'),
        ]);
    }

    public function testSaveInsertsNewDocument(): void
    {
        $result = $this->createStub(Result::class);
        $result->method('fetchAssociative')->willReturn(false);

        $connection = $this->createMock(Connection::class);
        $connection->method('count')->willReturn(0);
        $connection->method('select')->willReturn($result);
        $connection->expects(self::once())->method('insert');

        $subject = $this->createSubjectWithConnection($connection);
        $task = $subject->save($this->index, ['id' => 'doc-1', 'title' => 'Test', 'content' => 'Body']);

        self::assertNull($task);
    }

    public function testSaveUpdatesExistingDocument(): void
    {
        $selectResult = $this->createStub(Result::class);
        $selectResult->method('fetchAssociative')->willReturn(false);

        $connection = $this->createMock(Connection::class);
        $connection->method('count')->willReturn(1);
        $connection->method('select')->willReturn($selectResult);
        $connection->expects(self::once())->method('update');

        $subject = $this->createSubjectWithConnection($connection);
        $task = $subject->save($this->index, ['id' => 'doc-1', 'title' => 'Updated', 'content' => 'Body']);

        self::assertNull($task);
    }

    public function testSaveReturnsSyncTaskWithOptions(): void
    {
        $selectResult = $this->createStub(Result::class);
        $selectResult->method('fetchAssociative')->willReturn(false);

        $connection = $this->createStub(Connection::class);
        $connection->method('count')->willReturn(0);
        $connection->method('select')->willReturn($selectResult);

        $subject = $this->createSubjectWithConnection($connection);
        $task = $subject->save(
            $this->index,
            ['id' => 'doc-1', 'title' => 'Test', 'content' => 'Body'],
            ['return_slow_promise_result' => true],
        );

        self::assertInstanceOf(SyncTask::class, $task);
    }

    public function testSaveLogsExceptionOnDatabaseError(): void
    {
        $selectResult = $this->createStub(Result::class);
        $selectResult->method('fetchAssociative')->willReturn(false);

        $connection = $this->createStub(Connection::class);
        $connection->method('count')->will(self::throwException(new \Exception('DB error')));
        $connection->method('select')->willReturn($selectResult);

        $subject = $this->createSubjectWithConnection($connection);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');
        $subject->setLogger($logger);

        $subject->save($this->index, ['id' => 'doc-1', 'title' => 'Test', 'content' => 'Body']);
    }

    public function testDeleteRemovesDocument(): void
    {
        $selectResult = $this->createStub(Result::class);
        $selectResult->method('fetchAssociative')->willReturn(['uid' => 42]);

        $connection = $this->createMock(Connection::class);
        $connection->method('select')->willReturn($selectResult);
        $connection->expects(self::atLeastOnce())->method('delete');

        $subject = $this->createSubjectWithConnection($connection);
        $task = $subject->delete($this->index, 'doc-1');

        self::assertNull($task);
    }

    public function testDeleteReturnsSyncTaskWithOptions(): void
    {
        $selectResult = $this->createStub(Result::class);
        $selectResult->method('fetchAssociative')->willReturn(false);

        $connection = $this->createStub(Connection::class);
        $connection->method('select')->willReturn($selectResult);

        $subject = $this->createSubjectWithConnection($connection);
        $task = $subject->delete($this->index, 'doc-1', ['return_slow_promise_result' => true]);

        self::assertInstanceOf(SyncTask::class, $task);
    }

    public function testDeleteHandlesMissingDocument(): void
    {
        $selectResult = $this->createStub(Result::class);
        $selectResult->method('fetchAssociative')->willReturn(false);

        $connection = $this->createMock(Connection::class);
        $connection->method('select')->willReturn($selectResult);
        $connection->expects(self::once())->method('delete');

        $subject = $this->createSubjectWithConnection($connection);
        $subject->delete($this->index, 'nonexistent');
    }

    public function testBulkProcessesSavesAndDeletes(): void
    {
        $selectResult = $this->createStub(Result::class);
        $selectResult->method('fetchAssociative')->willReturn(false);

        $connection = $this->createStub(Connection::class);
        $connection->method('count')->willReturn(0);
        $connection->method('select')->willReturn($selectResult);

        $subject = $this->createSubjectWithConnection($connection);
        $task = $subject->bulk(
            $this->index,
            [['id' => 'doc-1', 'title' => 'A', 'content' => 'X']],
            ['doc-2'],
        );

        self::assertNull($task);
    }

    public function testBulkReturnsSyncTaskWithOptions(): void
    {
        $selectResult = $this->createStub(Result::class);
        $selectResult->method('fetchAssociative')->willReturn(false);

        $connection = $this->createStub(Connection::class);
        $connection->method('count')->willReturn(0);
        $connection->method('select')->willReturn($selectResult);

        $subject = $this->createSubjectWithConnection($connection);
        $task = $subject->bulk(
            $this->index,
            [['id' => 'doc-1', 'title' => 'A', 'content' => 'X']],
            [],
            100,
            ['return_slow_promise_result' => true],
        );

        self::assertInstanceOf(SyncTask::class, $task);
    }

    public function testSaveSyncsTags(): void
    {
        $selectUidResult = $this->createStub(Result::class);
        $selectUidResult->method('fetchAssociative')->willReturn(['uid' => 10]);

        $connection = $this->createMock(Connection::class);
        $connection->method('count')->willReturn(0);
        $connection->method('select')->willReturn($selectUidResult);
        $connection->expects(self::atLeastOnce())->method('insert');

        $indexWithTags = new Index('default', [
            'id' => new IdentifierField('id'),
            'title' => new TextField('title'),
            'content' => new TextField('content'),
            'tags' => new TextField('tags', multiple: true),
        ]);

        $subject = $this->createSubjectWithConnection($connection);
        $subject->save($indexWithTags, ['id' => 'doc-1', 'title' => 'Test', 'content' => 'Body', 'tags' => ['php', 'typo3']]);
    }
}
