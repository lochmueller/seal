<?php

declare(strict_types=1);

namespace Lochmueller\Seal\EventListener;

use CmsIg\Seal\Engine;
use Lochmueller\Seal\Adapter\Typo3Adapter;
use Lochmueller\Seal\Event\BuildEngineEvent;
use Lochmueller\Seal\Schema\SchemaBuilder;
use TYPO3\CMS\Core\Attribute\AsEventListener;

final class BuildEngineDefaultEventListener
{
    public function __construct(protected SchemaBuilder $schemaBuilder){}

    #[AsEventListener('seal-build-default-engine')]
    public function buildDefaultEngine(BuildEngineEvent $event): void
    {
        if ($event->engine === null) {
            $event->engine = new Engine(
                new Typo3Adapter(),
                $this->schemaBuilder->getSchema(),
            );

        }
    }

}
