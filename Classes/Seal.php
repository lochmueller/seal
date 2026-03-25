<?php

declare(strict_types=1);

namespace Lochmueller\Seal;

use CmsIg\Seal\EngineInterface;
use Lochmueller\Seal\Engine\EngineFactory;
use Lochmueller\Seal\Schema\SchemaBuilder;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class Seal
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

    public function getIndexNameBySite(SiteInterface $site): string
    {
        // Add logic to change the default index name e.g. by DSN configuration in the site
        // keep in mind, that you have to use BuildSchemaEvent to create a new schema
        return SchemaBuilder::DEFAULT_INDEX;
    }
}
