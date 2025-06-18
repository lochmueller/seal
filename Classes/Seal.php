<?php

declare(strict_types=1);

namespace Lochmueller\Seal;

use CmsIg\Seal\EngineInterface;
use Lochmueller\Seal\Event\BuildEngineEvent;
use Lochmueller\Seal\Exception\NoSealEngineException;
use Psr\EventDispatcher\EventDispatcherInterface;

class Seal
{
    public function __construct(protected EventDispatcherInterface $eventDispatcher)
    {
    }

    public function buildEngine(): EngineInterface
    {
        /** @var BuildEngineEvent $buildEngine */
        $buildEngine = $this->eventDispatcher->dispatch(new BuildEngineEvent());

        if ($buildEngine->engine !== null) {
            return $buildEngine->engine;
        }

        throw new NoSealEngineException('No EXT:seal engine engine found', 123789123);
    }


}
