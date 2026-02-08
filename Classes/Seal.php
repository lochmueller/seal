<?php

declare(strict_types=1);

namespace Lochmueller\Seal;

use CmsIg\Seal\EngineInterface;
use Lochmueller\Seal\Engine\EngineFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class Seal implements SingletonInterface
{
    /**
     * @var array<string, EngineInterface>
     */
    private array $requestCache = [];

    public function __construct(protected EngineFactory $engineFactory) {}

    public function buildEngineBySite(SiteInterface $site): EngineInterface
    {
        if (isset($this->requestCache[$site->getIdentifier()])) {
            return $this->requestCache[$site->getIdentifier()];
        }
        $this->requestCache[$site->getIdentifier()] = $this->engineFactory->buildEngineBySite($site);
        return $this->requestCache[$site->getIdentifier()];
    }
}
