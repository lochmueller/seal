<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Configuration;

use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class ConfigurationLoader
{
    public function loadBySite(SiteInterface $site): Configuration
    {
        return Configuration::createByArray((array) $site->getConfiguration());
    }


}
