<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Adapter\Typo3;

use CmsIg\Seal\Schema\Field\IdentifierField;
use CmsIg\Seal\Schema\Index;
use Lochmueller\Seal\Adapter\Typo3\Typo3AdapterHelper;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

class Typo3AdapterHelperTest extends AbstractTest
{
    private Typo3AdapterHelper $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new Typo3AdapterHelper();
    }

    public function testGetTableNameReturnsCorrectPrefix(): void
    {
        $index = new Index('default', [
            'id' => new IdentifierField('id'),
        ]);

        $result = $this->subject->getTableName($index);

        self::assertSame('tx_seal_domain_model_index_default', $result);
    }

    public function testGetTableNameWithCustomIndexName(): void
    {
        $index = new Index('products', [
            'id' => new IdentifierField('id'),
        ]);

        $result = $this->subject->getTableName($index);

        self::assertSame('tx_seal_domain_model_index_products', $result);
    }

    public function testGetTableNameWithDifferentIndexNames(): void
    {
        $helper = $this->subject;

        $indexA = new Index('alpha', ['id' => new IdentifierField('id')]);
        $indexB = new Index('beta', ['id' => new IdentifierField('id')]);

        self::assertNotSame($helper->getTableName($indexA), $helper->getTableName($indexB));
    }
}
