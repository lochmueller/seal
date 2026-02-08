<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Filter;

use Psr\Http\Message\RequestInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class Filter
{
    /**
     * @param iterable<FilterInterface> $filters
     */
    public function __construct(
        #[AutowireIterator('seal.filter')]
        protected iterable $filters,
    ) {}

    /**
     * @param array<int, \CmsIg\Seal\Search\Condition\SearchCondition> $filter
     * @param array<string, mixed> $filterItem
     * @return array<int, \CmsIg\Seal\Search\Condition\SearchCondition>
     */
    public function addFilterConfiguration(array $filter, array $filterItem, RequestInterface $request): array
    {
        foreach ($this->filters as $filterItemValue) {
            if ($filterItemValue->getType() === $filterItem['type']) {
                $filter = array_merge($filter, $filterItemValue->getFilterConfiguration($filterItem, $request));
            }
        }

        return $filter;
    }

}
