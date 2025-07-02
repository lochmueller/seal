<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Adapter\Dbal;

use CmsIg\Seal\Adapter\AdapterFactoryInterface;
use CmsIg\Seal\Adapter\AdapterInterface;

class DbalAdapterFactory implements AdapterFactoryInterface
{
    public function createAdapter(array $dsn): AdapterInterface
    {
        // TODO: Implement createAdapter() method.
    }

    public static function getName(): string
    {
        return 'dbal';
    }
}
