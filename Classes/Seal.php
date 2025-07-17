<?php

declare(strict_types=1);

namespace Lochmueller\Seal;

use CmsIg\Seal\EngineInterface;
use Lochmueller\Seal\Engine\EngineFactory;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class Seal
{
    public function __construct(protected EngineFactory $engineFactory) {}

    public function buildEngineBySite(SiteInterface $site): EngineInterface
    {
        static $requestCache = [];
        if (isset($requestCache[$site->getIdentifier()])) {
            return $requestCache[$site->getIdentifier()];
        }
        $requestCache[$site->getIdentifier()] = $this->engineFactory->buildEngineBySite($site);
        return $requestCache[$site->getIdentifier()];
    }


}
