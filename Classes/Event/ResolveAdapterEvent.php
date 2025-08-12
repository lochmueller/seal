<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Event;

use CmsIg\Seal\Adapter\AdapterInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

final class ResolveAdapterEvent
{
    public function __construct(public array $searchDsn, public SiteInterface $site, public ?AdapterInterface $adapter = null) {}

}
