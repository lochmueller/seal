<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Event;

use Lochmueller\Seal\Dto\DsnDto;

final class AfterDsnParsedEvent
{
    public function __construct(
        public DsnDto $dsnDto,
        public readonly string $originalDsn,
    ) {}
}
