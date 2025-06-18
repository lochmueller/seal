<?php

namespace Lochmueller\Seal\Command;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'seal:schema',
    description: 'Update the schema in SEAL adapters',
)]
class SchemaCommand
{

}