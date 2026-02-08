<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Configuration;

use Lochmueller\Seal\Configuration\Configuration;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

class ConfigurationTest extends AbstractTest
{
    public function testConstructorSetsProperties(): void
    {
        $configuration = new Configuration(
            searchDsn: 'elasticsearch://localhost:9200',
            autocompleteMinCharacters: 5,
            itemsPerPage: 20,
        );

        self::assertSame('elasticsearch://localhost:9200', $configuration->searchDsn);
        self::assertSame(5, $configuration->autocompleteMinCharacters);
        self::assertSame(20, $configuration->itemsPerPage);
    }

    public function testCreateByArrayWithFullConfiguration(): void
    {
        $configuration = Configuration::createByArray([
            'sealSearchDsn' => 'meilisearch://localhost:7700',
            'sealAutocompleteMinCharacters' => 2,
            'sealItemsPerPage' => 25,
        ]);

        self::assertSame('meilisearch://localhost:7700', $configuration->searchDsn);
        self::assertSame(2, $configuration->autocompleteMinCharacters);
        self::assertSame(25, $configuration->itemsPerPage);
    }

    public function testCreateByArrayWithDefaults(): void
    {
        $configuration = Configuration::createByArray([]);

        self::assertSame('typo3://', $configuration->searchDsn);
        self::assertSame(3, $configuration->autocompleteMinCharacters);
        self::assertSame(10, $configuration->itemsPerPage);
    }

    public function testCreateByArrayWithPartialConfiguration(): void
    {
        $configuration = Configuration::createByArray([
            'sealSearchDsn' => 'loupe://',
        ]);

        self::assertSame('loupe://', $configuration->searchDsn);
        self::assertSame(3, $configuration->autocompleteMinCharacters);
        self::assertSame(10, $configuration->itemsPerPage);
    }

    public function testCreateByArrayCastsTypes(): void
    {
        $configuration = Configuration::createByArray([
            'sealSearchDsn' => 123,
            'sealAutocompleteMinCharacters' => '7',
            'sealItemsPerPage' => '15',
        ]);

        self::assertSame('123', $configuration->searchDsn);
        self::assertSame(7, $configuration->autocompleteMinCharacters);
        self::assertSame(15, $configuration->itemsPerPage);
    }
}
