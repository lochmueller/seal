<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Event;

use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

final readonly class ResultSearchEvent
{
    public function __construct(
        public string $searchTerm,
        public int $totalResults,
        public int $currentPageNumber,
        public Site $site,
        public SiteLanguage $language,
    ) {}
}
