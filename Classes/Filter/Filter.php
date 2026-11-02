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
     * @param array<int, \CmsIg\Seal\Search\Condition\EqualCondition|\CmsIg\Seal\Search\Condition\InCondition|\CmsIg\Seal\Search\Condition\SearchCondition|\CmsIg\Seal\Search\Condition\GeoDistanceCondition> $filter
     * @param array<string, mixed> $filterItem
     * @return array<int, \CmsIg\Seal\Search\Condition\EqualCondition|\CmsIg\Seal\Search\Condition\InCondition|\CmsIg\Seal\Search\Condition\SearchCondition|\CmsIg\Seal\Search\Condition\GeoDistanceCondition>
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

    /**
     * Hands every filter record to the filter that owns its type, so that the parsed
     * configuration, the current selection and the facet counts are added by the filter
     * itself. "partial" names the Fluid partial that renders the record afterwards.
     *
     * @param array<int, array<string, mixed>> $filterRows
     * @param array<string, mixed> $requestData
     * @param array<string, int> $facetCounts
     * @return array<int, array<string, mixed>>
     */
    public function enrichFilterRows(array $filterRows, array $requestData = [], array $facetCounts = []): array
    {
        foreach ($filterRows as &$filterRow) {
            $filter = $this->findByType((string) ($filterRow['type'] ?? ''));
            if ($filter === null) {
                continue;
            }

            $filterRow = $filter->enrichFilterRow($filterRow, $requestData, $facetCounts);
            $filterRow['partial'] = $filter->getPartialName();
        }
        unset($filterRow);

        return $filterRows;
    }

    /**
     * Several filters may contribute conditions for one type, but only one of them renders it:
     * the first registered filter wins, so that a project is able to override a shipped filter
     * by registering its own service earlier.
     */
    private function findByType(string $type): ?FilterInterface
    {
        if ($type === '') {
            return null;
        }

        foreach ($this->filters as $filter) {
            if ($filter->getType() === $type) {
                return $filter;
            }
        }

        return null;
    }
}
