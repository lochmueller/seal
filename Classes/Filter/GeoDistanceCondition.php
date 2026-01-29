<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Filter;

use Psr\Http\Message\RequestInterface;

class GeoDistanceCondition implements FilterInterface
{
    public function getType(): string
    {
        return 'geoDistanceCondition';
    }

    /**
     * @param array<string, mixed> $filterItem
     * @return array<int, \CmsIg\Seal\Search\Condition\SearchCondition>
     */
    public function getFilterConfiguration(array $filterItem, RequestInterface $request): array
    {
        // @todo implement
        return [];
    }
}
