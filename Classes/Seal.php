<?php

declare(strict_types=1);

namespace Lochmueller\Seal;

use CmsIg\Seal\Engine;
use CmsIg\Seal\EngineInterface;
use Lochmueller\Seal\Event\BuildEngineEvent;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Psr\EventDispatcher\EventDispatcherInterface;
use Seal\Adapter\Typo3Adapter;

class Seal
{
    public function __construct(protected EventDispatcherInterface $eventDispatcher) {}

    public function buildEngine(): EngineInterface
    {
        /** @var BuildEngineEvent $buildEngine */
        $buildEngine = $this->eventDispatcher->dispatch(new BuildEngineEvent());

        if ($buildEngine->engine !== null) {
            return $buildEngine->engine;
        }

        throw new \Exception('No engine found', 123789123);
    }


}
