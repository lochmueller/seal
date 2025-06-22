<?php

declare(strict_types=1);

namespace Lochmueller\Seal\EventListener\ResolveAdapter;

use CmsIg\Seal\Adapter\Typesense\TypesenseAdapter;
use Lochmueller\Seal\Adapter\Typo3Adapter;
use Lochmueller\Seal\Event\ResolveAdapterEvent;
use Lochmueller\Seal\Exception\AdapterNotFoundException;
use Typesense\Client;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Example: typesense://HOST:PORT/?protocol=http&api_key=xxxxxx
 */
class TypesenseResolveAdapterEventListener
{
    #[AsEventListener('seal-adapter-typesense')]
    public function indexPageContent(ResolveAdapterEvent $event): void
    {

        if ($event->searchDsn->type !== 'typesense') {
            return;
        }

        if (!class_exists(TypesenseAdapter::class)) {
            throw new AdapterNotFoundException(package: 'cmsig/seal-typesense-adapter');
        }

        $client = new Client(
            [
                'api_key' => $searchDsnDto->options['api_key'] ?? '',
                'nodes' => [
                    [
                        'host' => $searchDsnDto->host ?? 'localhost',
                        'port' => $searchDsnDto->port ?? '8108',
                        'protocol' => $searchDsnDto->options['protocol'] ?? 'http',
                    ],
                ],
            ],
        );
        $event->adapter = new TypesenseAdapter($client);
    }
}
