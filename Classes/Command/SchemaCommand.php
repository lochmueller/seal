<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(
    name: 'seal:schema',
    description: 'Update the schema in SEAL adapters',
)]
class SchemaCommand extends Command {}
