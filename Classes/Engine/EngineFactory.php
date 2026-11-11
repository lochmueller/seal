<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Engine;

use CmsIg\Seal\Adapter\AdapterFactory;
use CmsIg\Seal\Engine;
use CmsIg\Seal\EngineInterface;
use Lochmueller\Index\Configuration\ConfigurationLoader as IndexConfigurationLoader;
use Lochmueller\Index\Indexing\Record\RecordFieldDefinitionResolver;
use Lochmueller\Index\Indexing\Record\RecordSourceResolver;
use Lochmueller\Seal\Adapter\Typo3\Typo3Adapter;
use Lochmueller\Seal\Configuration\ConfigurationLoader;
use Lochmueller\Seal\DsnNormalizer;
use Lochmueller\Seal\DsnParser;
use Lochmueller\Seal\Event\ResolveAdapterEvent;
use Lochmueller\Seal\Exception\AdapterNotFoundException;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class EngineFactory
{
    public static ?SiteInterface $currentSite = null;

    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected ConfigurationLoader $configurationLoader,
        protected IndexConfigurationLoader $indexConfigurationLoader,
        protected SchemaBuilder $schemaBuilder,
        protected DsnParser $dsnParser,
        protected DsnNormalizer $dsnNormalizer,
        protected AdapterFactory $adapterFactory,
        protected RecordFieldDefinitionResolver $recordFieldDefinitionResolver,
        protected RecordSourceResolver $recordSourceResolver,
    ) {}

    public function buildEngineBySite(SiteInterface $site): EngineInterface
    {
        self::$currentSite = $site;
        $configuration = $this->configurationLoader->loadBySite($site);
        $searchDsn = $this->dsnNormalizer->normalize($configuration->searchDsn);
        $dsn = $this->dsnParser->parse($searchDsn);

        try {
            $adapter = $this->adapterFactory->createAdapter($searchDsn);
        } catch (\InvalidArgumentException) {
            $adapter = null;
        }
        $resolveAdapterEvent = new ResolveAdapterEvent($dsn, $site, $adapter);
        $this->eventDispatcher->dispatch($resolveAdapterEvent);

        if ($resolveAdapterEvent->adapter === null) {
            throw new AdapterNotFoundException('No valid adapter found for site "' . $site->getIdentifier() . '"', 23482934);
        }

        $recordFieldDefinitions = $this->getRecordFieldDefinitions($site);
        if ($resolveAdapterEvent->adapter instanceof Typo3Adapter) {
            $baseFields = $this->schemaBuilder->getPageIndex()->fields;
            foreach (array_keys($recordFieldDefinitions) as $fieldName) {
                if (!isset($baseFields[$fieldName])) {
                    throw new \InvalidArgumentException(
                        'Dynamic record fields require an adapter with a dynamic schema. '
                        . 'The TYPO3 database adapter needs custom database columns for field "' . $fieldName . '".',
                    );
                }
            }
        }

        return new Engine(
            $resolveAdapterEvent->adapter,
            $this->schemaBuilder->getSchema($recordFieldDefinitions),
        );
    }

    /**
     * @return array<string, array{
     *     source: string,
     *     type: string,
     *     multiple: bool,
     *     searchable: bool,
     *     filterable: bool,
     *     sortable: bool,
     *     distinct: bool,
     *     facet: bool,
     *     options: array<string, mixed>
     * }>
     */
    private function getRecordFieldDefinitions(SiteInterface $site): array
    {
        $definitions = [];
        foreach ($this->indexConfigurationLoader->loadAllBySite($site) as $configuration) {
            if (!$configuration->technology->supportsRecordIndexing()) {
                continue;
            }

            foreach ($this->recordSourceResolver->resolve($configuration) as $source) {
                foreach ($this->recordFieldDefinitionResolver->resolve($source->table, $source->fields) as $name => $definition) {
                    if (isset($definitions[$name])) {
                        $existingDefinition = $definitions[$name];
                        unset($existingDefinition['source']);
                        $comparisonDefinition = $definition;
                        unset($comparisonDefinition['source']);
                        if ($existingDefinition !== $comparisonDefinition) {
                            throw new \UnexpectedValueException('Record field "' . $name . '" has conflicting schema definitions.');
                        }
                        continue;
                    }
                    $definitions[$name] = $definition;
                }
            }
        }

        return $definitions;
    }
}
