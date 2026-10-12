<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Dashboard\Provider;

use Lochmueller\Seal\Schema\SchemaBuilder;
use Lochmueller\Seal\Seal;
use ReflectionProperty;
use TYPO3\CMS\Core\Site\SiteFinder;

class IndexDocumentCountDataProvider implements TableDataProviderInterface
{
    public function __construct(
        private readonly Seal $seal,
        private readonly SiteFinder $siteFinder,
        private readonly SchemaBuilder $schemaBuilder,
    ) {}

    public function getHeaders(): array
    {
        return [
            'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:dashboard.column.site',
            'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:dashboard.column.index',
            'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:dashboard.column.documents',
            'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:dashboard.column.adapter',
        ];
    }

    public function getRows(): array
    {
        $sites = $this->siteFinder->getAllSites();
        if ($sites === []) {
            return [];
        }

        $indexNames = array_keys($this->schemaBuilder->getSchema()->indexes);
        $rows = [];

        foreach ($sites as $site) {
            try {
                $engine = $this->seal->buildEngineBySite($site);

                $reflectionProperty = new ReflectionProperty($engine, 'adapter');
                $adapter = $reflectionProperty->getValue($engine);
                $adapterName = is_object($adapter) ? (new \ReflectionClass($adapter))->getShortName() : '';

                foreach ($indexNames as $indexName) {
                    $rows[] = [
                        $site->getIdentifier(),
                        $indexName,
                        (string) $engine->countDocuments($indexName),
                        $adapterName,
                    ];
                }
            } catch (\Exception $exception) {
                $rows[] = [
                    $site->getIdentifier(),
                    '-',
                    'Error: ' . $exception->getMessage(),
                    '-',
                ];
            }
        }

        return $rows;
    }
}
