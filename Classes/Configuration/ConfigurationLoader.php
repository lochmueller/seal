<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Configuration;

use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class ConfigurationLoader
{
    public function loadBySite(SiteInterface $site): Configuration
    {
        if (!$site instanceof Site) {
            throw new \InvalidArgumentException('Expected instance of Site, got ' . $site::class);
        }
        return Configuration::createByArray((array) $site->getConfiguration());
    }
}
