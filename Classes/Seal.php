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
        return $this->engineFactory->buildEngineBySite($site);
    }


}
