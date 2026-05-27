<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Event;

use CmsIg\Seal\Search\SearchBuilder;
use Lochmueller\Seal\Configuration\Configuration;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

final class ModifySearchBuilderEvent
{
    public function __construct(
        public SearchBuilder $searchBuilder,
        public readonly SiteInterface $site,
        public readonly SiteLanguage $language,
        public readonly Configuration $configuration,
    ) {
    }
}
