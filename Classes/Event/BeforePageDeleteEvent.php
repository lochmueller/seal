<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Event;

use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class BeforePageDeleteEvent
{
    public function __construct(
        public string $documentIdentifier,
        public string $uri,
        public readonly SiteInterface $site,
        public readonly string $indexName,
        public bool   $deletePage = true,
    ) {}
}
