<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Filter;

use Psr\Http\Message\RequestInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class Filter
{
    public function __construct(
        #[AutowireIterator('seal.filter')]
        protected iterable $filters,
    ) {}

    public function addFilterConfiguration(array $filter, array $filterItem, RequestInterface $request)
    {

        foreach ($this->filters as $filterItemValue) {
            /** @var $filterItemValue FilterInterface */
            if ($filterItemValue->getType() === $filterItem['type']) {
                $filter = array_merge($filter, $filterItemValue->getFilterConfiguration($filterItem, $request));
            }
        }

        return $filter;
    }

}
