<?php

declare(strict_types=1);

namespace Lochmueller\Seal\EventListener;

use CmsIg\Seal\Engine;
use Lochmueller\Seal\Event\BuildEngineEvent;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Seal\Adapter\Typo3Adapter;
use TYPO3\CMS\Core\Attribute\AsEventListener;

final class BuildEngineDefaultEventListener
{
    #[AsEventListener('seal-build-default-engine')]
    public function buildDefaultEngine(BuildEngineEvent $event)
    {

        if ($event->engine === null) {

            $builder = new SchemaBuilder();

            $event->engine = new Engine(
                new Typo3Adapter(),
                $builder->getSchema(),
            );

        }
    }

}
