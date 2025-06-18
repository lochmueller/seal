<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Event;

use CmsIg\Seal\Schema\Schema;

final class BuildSchemaEvent
{
    public function __construct(public Schema $schema) {}

}
