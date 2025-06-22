<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Engine;

use CmsIg\Seal\Engine;
use CmsIg\Seal\EngineInterface;
use Lochmueller\Seal\Adapter\Typo3Adapter;
use Lochmueller\Seal\Dto\SearchDsnDto;
use Lochmueller\Seal\Event\BuildEngineEvent;
use Lochmueller\Seal\Event\ResolveAdapterEvent;
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
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class EngineFactory
{
    public function __construct(
        protected Context                  $context,
        protected EventDispatcherInterface $eventDispatcher,
        protected SchemaBuilder            $schemaBuilder,
    ) {}

    public function buildEngine(): EngineInterface
    {
        $site = $this->getSite();
        return $this->buildEngineBySite($site);
    }

    public function buildEngineBySite(SiteInterface $site): EngineInterface
    {
        // œtodo move to site configuration
        $searchDsn = 'typo3://localhost';
        $searchDsn = 'loupe://localhost/?directory=varPath';

        /** @var ResolveAdapterEvent $resolveAdapterEvent */
        $resolveAdapterEvent = $this->eventDispatcher->dispatch(new ResolveAdapterEvent(new SearchDsnDto($searchDsn), $site));

        DebuggerUtility::var_dump($resolveAdapterEvent);
        return new Engine(
            $resolveAdapterEvent->adapter,
            $this->schemaBuilder->getSchema(),
        );
    }

    protected function getSite(): ?SiteInterface
    {
        return $GLOBALS['TYPO3_REQUEST']?->getAttribute('site');
    }
}
