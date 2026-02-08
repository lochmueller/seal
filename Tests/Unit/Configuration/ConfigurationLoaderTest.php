<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Configuration;

use Lochmueller\Seal\Configuration\Configuration;
use Lochmueller\Seal\Configuration\ConfigurationLoader;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class ConfigurationLoaderTest extends AbstractTest
{
    private ConfigurationLoader $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new ConfigurationLoader();
    }

    public function testLoadBySiteReturnConfiguration(): void
    {
        $site = $this->createStub(Site::class);
        $site->method('getConfiguration')->willReturn([
            'sealSearchDsn' => 'elasticsearch://localhost:9200',
            'sealAutocompleteMinCharacters' => 4,
            'sealItemsPerPage' => 20,
        ]);

        $result = $this->subject->loadBySite($site);

        self::assertInstanceOf(Configuration::class, $result);
        self::assertSame('elasticsearch://localhost:9200', $result->searchDsn);
        self::assertSame(4, $result->autocompleteMinCharacters);
        self::assertSame(20, $result->itemsPerPage);
    }

    public function testLoadBySiteWithDefaultValues(): void
    {
        $site = $this->createStub(Site::class);
        $site->method('getConfiguration')->willReturn([]);

        $result = $this->subject->loadBySite($site);

        self::assertSame('typo3://', $result->searchDsn);
        self::assertSame(3, $result->autocompleteMinCharacters);
        self::assertSame(10, $result->itemsPerPage);
    }

    public function testLoadBySiteThrowsExceptionForNonSiteInstance(): void
    {
        $site = $this->createStub(SiteInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected instance of Site');

        $this->subject->loadBySite($site);
    }
}
