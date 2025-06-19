<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Event;

use CmsIg\Seal\EngineInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

final class BuildEngineEvent
{
    public function __construct(
        public EngineInterface $engine,
        public readonly SiteInterface $site,
    ) {}

}
