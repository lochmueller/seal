<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Event;

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Schema\Schema;
use CmsIg\Seal\Search\SearchBuilder;
use Lochmueller\Seal\Configuration\Configuration;
use Lochmueller\Seal\Event\ModifySearchBuilderEvent;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class ModifySearchBuilderEventTest extends AbstractTest
{
    public function testConstructorSetsProperties(): void
    {
        $searchBuilder = $this->createSearchBuilder();
        $site = $this->createStub(SiteInterface::class);
        $language = $this->createStub(SiteLanguage::class);
        $configuration = new Configuration('typo3://', 3, 10, SimplePagination::class, 6);

        $event = new ModifySearchBuilderEvent($searchBuilder, $site, $language, $configuration);

        self::assertSame($searchBuilder, $event->searchBuilder);
        self::assertSame($site, $event->site);
        self::assertSame($language, $event->language);
        self::assertSame($configuration, $event->configuration);
    }

    public function testSearchBuilderIsReplaceable(): void
    {
        $event = new ModifySearchBuilderEvent(
            $this->createSearchBuilder(),
            $this->createStub(SiteInterface::class),
            $this->createStub(SiteLanguage::class),
            new Configuration('typo3://', 3, 10, SimplePagination::class, 6),
        );

        $replacement = $this->createSearchBuilder();
        $event->searchBuilder = $replacement;

        self::assertSame($replacement, $event->searchBuilder);
    }

    private function createSearchBuilder(): SearchBuilder
    {
        return new SearchBuilder(new Schema([]), $this->createStub(SearcherInterface::class));
    }
}
