<?php

declare(strict_types=1);

use CmsIg\Seal\Adapter\AdapterFactory;
use CmsIg\Seal\Adapter\Elasticsearch\ElasticsearchAdapterFactory;
use CmsIg\Seal\Adapter\Loupe\LoupeAdapterFactory;
use CmsIg\Seal\Adapter\Meilisearch\MeilisearchAdapterFactory;
use CmsIg\Seal\Adapter\Memory\MemoryAdapterFactory;
use CmsIg\Seal\Adapter\Opensearch\OpensearchAdapterFactory;
use CmsIg\Seal\Adapter\RediSearch\RediSearchAdapterFactory;
use CmsIg\Seal\Adapter\Algolia\AlgoliaAdapterFactory;
use CmsIg\Seal\Adapter\Multi\MultiAdapterFactory;
use CmsIg\Seal\Adapter\ReadWrite\ReadWriteAdapterFactory;
use CmsIg\Seal\Adapter\Solr\SolrAdapterFactory;
use CmsIg\Seal\Adapter\Typesense\TypesenseAdapterFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Dashboard\Widgets\ListWidget;
use Lochmueller\Seal\Adapter\Typo3\Typo3AdapterFactory;
use Lochmueller\Seal\Dashboard\Provider\IndexDocumentCountDataProvider;
use Lochmueller\Seal\Dashboard\Provider\LatestSearchesDataProvider;
use Lochmueller\Seal\Dashboard\Provider\TopSearchesDataProvider;

return function (ContainerConfigurator $container): void {
    $services = $container->services();

    $checkedFactories = [
        LoupeAdapterFactory::class,
        TypesenseAdapterFactory::class,
        MemoryAdapterFactory::class,
        RediSearchAdapterFactory::class,
        ElasticsearchAdapterFactory::class,
        OpensearchAdapterFactory::class,
        MeilisearchAdapterFactory::class,
        AlgoliaAdapterFactory::class,
        MultiAdapterFactory::class,
        ReadWriteAdapterFactory::class,
        SolrAdapterFactory::class,
        Typo3AdapterFactory::class,
    ];

    foreach ($checkedFactories as $factory) {
        if (class_exists($factory)) {
            $services
                ->set($factory)
                ->autowire()
                ->autoconfigure()
                ->tag('seal.adapter_factory');
        }
    }

    // Collect every tagged factory rather than a fixed list, so adapters shipped by
    // other extensions (e.g. EXT:seal_ai's "ai://") register themselves. Keyed by the
    // static AdapterFactoryInterface::getName(), which is the key AdapterFactory
    // looks up the DSN scheme by.
    $services->set(AdapterFactory::class)
        ->arg('$factories', tagged_iterator('seal.adapter_factory', null, 'getName'));

    if (class_exists(ListWidget::class)) {
        $services->set('dashboard.widget.seal.latestSearches')
            ->class(ListWidget::class)
            ->arg('$backendViewFactory', new Reference(BackendViewFactory::class))
            ->arg('$dataProvider', new Reference(LatestSearchesDataProvider::class))
            ->tag('dashboard.widget', [
                'identifier' => 'seal-latest-searches',
                'groupNames' => 'seal',
                'title' => 'Neueste 20 Suchanfragen',
                'description' => 'Zeigt die 20 neuesten Suchanfragen an',
                'iconIdentifier' => 'content-widget-list',
                'height' => 'medium',
                'width' => 'small',
            ]);

        $services->set('dashboard.widget.seal.topSearches')
            ->class(ListWidget::class)
            ->arg('$backendViewFactory', new Reference(BackendViewFactory::class))
            ->arg('$dataProvider', new Reference(TopSearchesDataProvider::class))
            ->tag('dashboard.widget', [
                'identifier' => 'seal-top-searches',
                'groupNames' => 'seal',
                'title' => 'Top 10 Suchen des Monats',
                'description' => 'Zeigt die 10 meistgesuchten Begriffe des aktuellen Monats an',
                'iconIdentifier' => 'content-widget-list',
                'height' => 'medium',
                'width' => 'small',
            ]);

        $services->set('dashboard.widget.seal.indexDocumentCount')
            ->class(ListWidget::class)
            ->arg('$backendViewFactory', new Reference(BackendViewFactory::class))
            ->arg('$dataProvider', new Reference(IndexDocumentCountDataProvider::class))
            ->tag('dashboard.widget', [
                'identifier' => 'seal-index-document-count',
                'groupNames' => 'seal',
                'title' => 'Index-Dokumentenanzahl',
                'description' => 'Zeigt die Anzahl der Dokumente in den SEAL-Indizes pro Site an',
                'iconIdentifier' => 'content-widget-list',
                'height' => 'medium',
                'width' => 'small',
            ]);
    }
};
