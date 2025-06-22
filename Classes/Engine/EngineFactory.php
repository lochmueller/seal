<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Engine;

use CmsIg\Seal\Engine;
use CmsIg\Seal\EngineInterface;
use Lochmueller\Seal\Dto\SearchDsnDto;
use Lochmueller\Seal\Event\ResolveAdapterEvent;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class EngineFactory
{
    public function __construct(
        protected Context                  $context,
        protected EventDispatcherInterface $eventDispatcher,
        protected SchemaBuilder            $schemaBuilder,
    ) {}

    public function buildEngine(): EngineInterface
    {
        $site = $this->getSite();
        return $this->buildEngineBySite($site);
    }

    public function buildEngineBySite(SiteInterface $site): EngineInterface
    {
        $siteConfiguration = $site->getConfiguration();
        /** @var ResolveAdapterEvent $resolveAdapterEvent */
        $resolveAdapterEvent = $this->eventDispatcher->dispatch(new ResolveAdapterEvent(new SearchDsnDto($siteConfiguration['seal_search_dsn'] ?? null), $site));

        return new Engine(
            $resolveAdapterEvent->adapter,
            $this->schemaBuilder->getSchema(),
        );
    }

    protected function getSite(): ?SiteInterface
    {
        return $GLOBALS['TYPO3_REQUEST']?->getAttribute('site');
    }
}
