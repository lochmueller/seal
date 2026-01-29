<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Engine;

use CmsIg\Seal\Adapter\AdapterFactoryInterface;
use CmsIg\Seal\Engine;
use CmsIg\Seal\EngineInterface;
use Lochmueller\Seal\Configuration\ConfigurationLoader;
use Lochmueller\Seal\DsnParser;
use Lochmueller\Seal\Event\ResolveAdapterEvent;
use Lochmueller\Seal\Exception\AdapterNotFoundException;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class EngineFactory
{
    /**
     * @param iterable<AdapterFactoryInterface> $adapterFactories
     */
    public function __construct(
        protected Context                  $context,
        protected EventDispatcherInterface $eventDispatcher,
        protected ConfigurationLoader $configurationLoader,
        protected SchemaBuilder            $schemaBuilder,
        protected DsnParser            $dsnParser,
        #[AutowireIterator('seal.adapter_factory')]
        protected iterable                 $adapterFactories,
    ) {}

    public function buildEngineBySite(SiteInterface $site): EngineInterface
    {
        $configuration = $this->configurationLoader->loadBySite($site);
        $dsn = $this->dsnParser->parse($configuration->searchDsn);
        $adapter = null;

        foreach ($this->adapterFactories as $adapterFactory) {
            /** @var $adapterFactory AdapterFactoryInterface */
            if ($dsn->scheme === $adapterFactory::getName()) {
                $adapter = $adapterFactory->createAdapter($dsn->toArray());
            }
        }

        /** @var ResolveAdapterEvent $resolveAdapterEvent */
        $resolveAdapterEvent = $this->eventDispatcher->dispatch(new ResolveAdapterEvent($dsn, $site, $adapter));

        if ($resolveAdapterEvent->adapter === null) {
            throw new AdapterNotFoundException('No valid adapter found for site "' . $site->getIdentifier() . '"', 23482934);
        }

        return new Engine(
            $resolveAdapterEvent->adapter,
            $this->schemaBuilder->getSchema(),
        );
    }
}
