<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Filter;

use Psr\Http\Message\RequestInterface;

class TagCondition implements FilterInterface
{
    public function getType(): string
    {
        return 'tagCondition';
    }

    public function getFilterConfiguration(array $filterItem, RequestInterface $request): array
    {

        // @todo implement
        return [];
    }
}
