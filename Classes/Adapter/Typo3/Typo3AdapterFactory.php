<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Adapter\Typo3;

use CmsIg\Seal\Adapter\AdapterFactoryInterface;
use CmsIg\Seal\Adapter\AdapterInterface;

class Typo3AdapterFactory implements AdapterFactoryInterface
{
    public function __construct(
        protected Typo3Adapter $typo3Adapter,
    ) {}

    public function createAdapter(array $dsn): AdapterInterface
    {
        return $this->typo3Adapter;
    }

    public static function getName(): string
    {
        return 'typo3';
    }
}
