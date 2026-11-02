<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Filter;

use Psr\Http\Message\RequestInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * A filter owns everything that belongs to one entry of the "tx_seal_domain_model_filter"
 * type select: the search conditions it contributes, the values its form controls need and
 * the partial that renders them. Nothing outside of the implementation has to know the type
 * string, so a third party can ship a filter without patching the controllers or the
 * templates of this extension.
 *
 * Extend @see AbstractFilter instead of implementing this interface directly, so that filters
 * that need neither presentation values nor a custom partial stay as short as they are.
 */
#[AutoconfigureTag(name: 'seal.filter')]
interface FilterInterface
{
    public function getType(): string;

    /**
     * Path of the Fluid partial that renders the form controls of this filter, relative to the
     * configured partialRootPaths (e.g. "Form/TagCondition").
     */
    public function getPartialName(): string;

    /**
     * @param array<string, mixed> $filterItem
     * @return array<int, \CmsIg\Seal\Search\Condition\EqualCondition|\CmsIg\Seal\Search\Condition\InCondition|\CmsIg\Seal\Search\Condition\SearchCondition|\CmsIg\Seal\Search\Condition\GeoDistanceCondition>
     */
    public function getFilterConfiguration(array $filterItem, RequestInterface $request): array;

    /**
     * Adds everything the partial needs on top of the raw filter record: the parsed
     * configuration of the record, the current selection of the request and the facet counts
     * of the search result.
     *
     * @param array<string, mixed> $filterRow Raw record of "tx_seal_domain_model_filter"
     * @param array<string, mixed> $requestData Search state of the current request
     * @param array<string, int> $facetCounts Facet counts of the current search result
     * @return array<string, mixed>
     */
    public function enrichFilterRow(array $filterRow, array $requestData, array $facetCounts): array;
}
