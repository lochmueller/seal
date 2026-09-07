<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Filter;

use CmsIg\Seal\Search\Condition\Condition;
use Lochmueller\Seal\Resolver\SearchRequestDataResolver;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

class TagCondition implements FilterInterface
{
    public function __construct(
        private readonly TagConfigurationParser $parser,
        private readonly SearchRequestDataResolver $searchRequestDataResolver,
    ) {}

    public function getType(): string
    {
        return 'tagCondition';
    }

    /**
     * @param array<string, mixed> $filterItem
     * @return array<int, \CmsIg\Seal\Search\Condition\EqualCondition|\CmsIg\Seal\Search\Condition\InCondition|\CmsIg\Seal\Search\Condition\SearchCondition|\CmsIg\Seal\Search\Condition\GeoDistanceCondition>
     */
    public function getFilterConfiguration(array $filterItem, RequestInterface $request): array
    {
        $configuredTags = $this->parser->parse((string) ($filterItem['tags'] ?? ''));

        $allowedValues = array_map(
            static fn(array $tag): string => $tag['value'],
            $configuredTags,
        );

        $filterName = 'field_' . $filterItem['uid'];
        $selectedValues = [];
        if ($request instanceof ServerRequestInterface) {
            // Resolved instead of read from the parsed body: the search form is submitted via
            // GET, so the selected tags arrive as query parameters.
            $selectedValues = $this->searchRequestDataResolver->resolve($request)[$filterName] ?? [];
        }

        if (!is_array($selectedValues)) {
            $selectedValues = [];
        }

        // Only scalar strings of the request are usable and Condition::in() expects a list.
        $validValues = array_values(array_intersect(array_filter($selectedValues, is_string(...)), $allowedValues));

        $conditions = [];
        if (!empty($validValues)) {
            $conditions[] = Condition::in('tags', $validValues);
        }

        return $conditions;
    }
}
