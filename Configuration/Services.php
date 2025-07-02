<?php

use CmsIg\Seal\Adapter\Elasticsearch\ElasticsearchAdapterFactory;
use CmsIg\Seal\Adapter\Loupe\LoupeAdapterFactory;
use CmsIg\Seal\Adapter\Meilisearch\MeilisearchAdapterFactory;
use CmsIg\Seal\Adapter\Memory\MemoryAdapterFactory;
use CmsIg\Seal\Adapter\Opensearch\OpensearchAdapterFactory;
use CmsIg\Seal\Adapter\RediSearch\RediSearchAdapterFactory;
use CmsIg\Seal\Adapter\Typesense\TypesenseAdapterFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    $services = $container->services();

    $checkedFactories = [
        // @todo add all known Adapters
        LoupeAdapterFactory::class,
        TypesenseAdapterFactory::class,
        MemoryAdapterFactory::class,
        RediSearchAdapterFactory::class,
        ElasticsearchAdapterFactory::class,
        OpensearchAdapterFactory::class,
        MeilisearchAdapterFactory::class,
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

};