<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Engine;

use CmsIg\Seal\Adapter\AdapterFactory;
use CmsIg\Seal\Engine;
use CmsIg\Seal\EngineInterface;
use InvalidArgumentException;
use Lochmueller\Seal\Configuration\ConfigurationLoader;
use Lochmueller\Seal\DsnParser;
use Lochmueller\Seal\Event\ResolveAdapterEvent;
use Lochmueller\Seal\Exception\AdapterNotFoundException;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class EngineFactory
{
    /**
     *
     */
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected ConfigurationLoader $configurationLoader,
        protected SchemaBuilder            $schemaBuilder,
        protected DsnParser            $dsnParser,
        protected AdapterFactory           $adapterFactory,
    ) {}

    public function buildEngineBySite(SiteInterface $site): EngineInterface
    {
        $configuration = $this->configurationLoader->loadBySite($site);
        $dsn = $this->dsnParser->parse($configuration->searchDsn);
        try {
            $adapter = $this->adapterFactory->createAdapter($configuration->searchDsn);
        } catch (InvalidArgumentException) {
            $adapter = null;
        }
        $resolveAdapterEvent = new ResolveAdapterEvent($dsn, $site, $adapter);
        $this->eventDispatcher->dispatch($resolveAdapterEvent);

        if ($resolveAdapterEvent->adapter === null) {
            throw new AdapterNotFoundException('No valid adapter found for site "' . $site->getIdentifier() . '"', 23482934);
        }

        return new Engine(
            $resolveAdapterEvent->adapter,
            $this->schemaBuilder->getSchema(),
        );
    }
}
