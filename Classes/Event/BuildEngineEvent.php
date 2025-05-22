<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Event;

use CmsIg\Seal\EngineInterface;

final class BuildEngineEvent
{
    public function __construct(public ?EngineInterface $engine = null) {}

}
