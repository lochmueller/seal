<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Engine;

use CmsIg\Seal\Engine;
use CmsIg\Seal\EngineInterface;
use Lochmueller\Seal\Adapter\Typo3Adapter;
use Lochmueller\Seal\Event\BuildEngineEvent;
use Lochmueller\Seal\Exception\AdapterNotFoundException;
use Lochmueller\Seal\Exception\EngineNotFound;
use Lochmueller\Seal\Exception\NoSealEngineException;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mime\Exception\AddressEncoderException;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use Loupe\Loupe\LoupeFactory;
use CmsIg\Seal\Adapter\Loupe\LoupeAdapter;
use CmsIg\Seal\Adapter\Loupe\LoupeHelper;
use CmsIg\Seal\Adapter\Typesense\TypesenseAdapter;
use Typesense\Client;

class EngineFactory
{
    public function __construct(
        protected Context                  $context,
        protected EventDispatcherInterface $eventDispatcher,
        protected Typo3Adapter             $typo3Adapter,
        protected SchemaBuilder            $schemaBuilder,
        protected Environment              $environment,
    ) {}

    public function buildEngine(): EngineInterface
    {
        $site = $this->getSite();
        return $this->buildEngineBySite($site);
    }

    public function buildEngineBySite(SiteInterface $site): EngineInterface
    {
        $searchDsn = 'typo3://localhost';


        $searchDsn = 'loupe://localhost/?directory=varPath';

        // SEARCH_DSN
        // TYPO3: typo3://localhost
        // Typesense: typesense://HOST:PORT/?protocol=http&api_key=xxxxxx
        // Loupe: loupe://localhost/?directory=varPath/folderName

        $parts = parse_url($searchDsn);
        parse_str($parts['query'] ?? '', $options);


        switch ($parts['scheme'] ?? '') {
            case 'loupe':
                if (!class_exists(LoupeAdapter::class)) {
                    throw new AdapterNotFoundException(package: 'cmsig/seal-loupe-adapter');
                }
                $directory = $options['directory'] ?? 'varPath';

                foreach ($this->environment->toArray() as $key => $value) {
                    if (str_starts_with($directory, $key)) {
                        $directory = str_replace($key, $value, $directory);
                    }

                }

                $adapter = new LoupeAdapter(
                    new LoupeHelper(
                        new LoupeFactory(),
                        $directory,
                    ),
                );

                break;
            case 'typesense':
                if (!class_exists(TypesenseAdapter::class)) {
                    throw new AdapterNotFoundException(package: 'cmsig/seal-typesense-adapter');
                }

                $client = new Client(
                    [
                        'api_key' => $options['api_key'] ?? '',
                        'nodes' => [
                            [
                                'host' => $parts['host'] ?? 'localhost',
                                'port' => $parts['port'] ?? '8108',
                                'protocol' => $options['protocol'] ?? 'http',
                            ],
                        ],
                        #'client' => new CurlClient(Psr17FactoryDiscovery::findResponseFactory(), Psr17FactoryDiscovery::findStreamFactory()),
                    ],
                );
                $adapter = new TypesenseAdapter($client);
                break;
            case 'typo3':
            default:
                $adapter = $this->typo3Adapter;
                break;
        }


        // Default engine
        $engine = new Engine(
            $adapter,
            $this->schemaBuilder->getSchema(),
        );


        /** @var BuildEngineEvent $buildEngine */
        $buildEngine = $this->eventDispatcher->dispatch(new BuildEngineEvent($engine, $site));

        if ($buildEngine->engine !== null) {
            return $buildEngine->engine;
        }

        throw new EngineNotFound('No EXT:seal engine engine found', 123789123);
    }

    protected function getSite(): ?SiteInterface
    {
        return $GLOBALS['TYPO3_REQUEST']?->getAttribute('site');
    }
}
