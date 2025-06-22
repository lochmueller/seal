<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Event;

use CmsIg\Seal\Adapter\AdapterInterface;
use Lochmueller\Seal\Dto\SearchDsnDto;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class ResolveAdapterEvent
{
    public function __construct(public SearchDsnDto $searchDsn, public SiteInterface $site, public ?AdapterInterface $adapter = null) {}

}
