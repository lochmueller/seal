<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Adapter\Typo3;

use CmsIg\Seal\Schema\Field\IdentifierField;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\SyncTask;
use Lochmueller\Seal\Adapter\Typo3\Typo3AdapterHelper;
use Lochmueller\Seal\Adapter\Typo3\Typo3SchemaManager;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Schema\SchemaInformation;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;

class Typo3SchemaManagerTest extends AbstractTest
{
    private Index $index;

    protected function setUp(): void
    {
        parent::setUp();

        $this->index = new Index('default', [
            'id' => new IdentifierField('id'),
        ]);
    }

    /**
     * @param list<string> $tables
     */
    private function createSchemaInformation(array $tables): SchemaInformation
    {
        $doctrineConnection = $this->createStub(\Doctrine\DBAL\Connection::class);
        $doctrineConnection->method('getParams')->willReturn(['dbname' => 'test']);

        $runtimeCache = $this->createStub(FrontendInterface::class);
        $runtimeCache->method('get')->willReturn($tables);

        $persistentCache = $this->createStub(FrontendInterface::class);

        $cacheIdentifier = $this->createStub(PackageDependentCacheIdentifier::class);
        $cacheIdentifier->method('withPrefix')->willReturnSelf();
        $cacheIdentifier->method('withAdditionalHashedIdentifier')->willReturnSelf();
        $cacheIdentifier->method('toString')->willReturn('test-connection');

        return new SchemaInformation($doctrineConnection, $runtimeCache, $persistentCache, $cacheIdentifier);
    }

    /**
     * @param list<string> $tables
     */
    private function createSubject(array $tables, ?Connection $connection = null): Typo3SchemaManager
    {
        $schemaInformation = $this->createSchemaInformation($tables);

        if ($connection === null) {
            $connection = $this->createStub(Connection::class);
        }
        $connection->method('getSchemaInformation')->willReturn($schemaInformation);

        $adapterHelper = $this->createStub(Typo3AdapterHelper::class);
        $adapterHelper->method('getConnection')->willReturn($connection);
        $adapterHelper->method('getTableName')->willReturn('tx_seal_domain_model_index_default');

        return new Typo3SchemaManager($adapterHelper);
    }

    public function testExistIndexReturnsTrueWhenTableExists(): void
    {
        $subject = $this->createSubject(['tx_seal_domain_model_index_default', 'pages']);

        self::assertTrue($subject->existIndex($this->index));
    }

    public function testExistIndexReturnsFalseWhenTableDoesNotExist(): void
    {
        $subject = $this->createSubject(['pages', 'tt_content']);

        self::assertFalse($subject->existIndex($this->index));
    }

    public function testDropIndexTruncatesTables(): void
    {
        $truncatedTables = [];
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::exactly(3))
            ->method('truncate')
            ->willReturnCallback(function (string $tableName) use (&$truncatedTables): int {
                $truncatedTables[] = $tableName;
                return 0;
            });

        $subject = $this->createSubject(['tx_seal_domain_model_index_default'], $connection);
        $result = $subject->dropIndex($this->index);

        self::assertInstanceOf(SyncTask::class, $result);
        self::assertContains('tx_seal_domain_model_index_default', $truncatedTables);
        self::assertContains('tx_seal_domain_model_index_default_tag', $truncatedTables);
        self::assertContains('tx_seal_domain_model_index_default_mm_tag', $truncatedTables);
    }

    public function testDropIndexReturnsNullWhenTableDoesNotExist(): void
    {
        $subject = $this->createSubject(['pages']);

        $result = $subject->dropIndex($this->index);

        self::assertNull($result);
    }

    public function testCreateIndexReturnsSyncTaskWhenTableExists(): void
    {
        $subject = $this->createSubject(['tx_seal_domain_model_index_default']);
        $result = $subject->createIndex($this->index);

        self::assertInstanceOf(SyncTask::class, $result);
    }

    public function testCreateIndexThrowsExceptionWhenTableDoesNotExist(): void
    {
        $subject = $this->createSubject([]);

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1238123);

        $subject->createIndex($this->index);
    }
}
