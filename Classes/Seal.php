<?php

namespace Lochmueller\Searl;

use CmsIg\Seal\Engine;
use CmsIg\Seal\EngineInterface;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Seal\Adapter\Typo3Adapter;

class Seal
{


    public function buildEngine(): EngineInterface
    {

        $builder = new SchemaBuilder();

        return new Engine(
            new Typo3Adapter(),
            $builder->getSchema(),
        );
    }


}
