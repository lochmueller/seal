<?php

declare(strict_types=1);

namespace Lochmueller\Seal\EventListener;

use Lochmueller\Seal\Indexing\Cache\CacheIndexing;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

class AfterCacheableContentIsGeneratedEventListener
{
    public function __construct(
        private CacheIndexing $cacheIndexing,
    ) {}

    #[AsEventListener('seal-cache-indexer')]
    public function indexPageContent(AfterCacheableContentIsGeneratedEvent $event): void
    {
        $this->cacheIndexing->indexPageContentViaAfterCacheableContentIsGeneratedEvent($event);
    }
}
