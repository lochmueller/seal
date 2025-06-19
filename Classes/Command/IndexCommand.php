<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(
    name: 'seal:index',
    description: 'Index content to SEAL adapters',
)]
class IndexCommand extends Command {}
