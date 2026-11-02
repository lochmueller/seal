<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Filter;

/**
 * Default implementation of the parts of a filter that most filters do not need to change:
 * the partial is named after the filter type and the filter record is handed to the template
 * as it is.
 */
abstract class AbstractFilter implements FilterInterface
{
    public function getPartialName(): string
    {
        return 'Form/' . ucfirst($this->getType());
    }

    /**
     * @param array<string, mixed> $filterRow
     * @param array<string, mixed> $requestData
     * @param array<string, int> $facetCounts
     * @return array<string, mixed>
     */
    public function enrichFilterRow(array $filterRow, array $requestData, array $facetCounts): array
    {
        return $filterRow;
    }
}
