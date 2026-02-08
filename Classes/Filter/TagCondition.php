<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Filter;

use CmsIg\Seal\Search\Condition\Condition;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

class TagCondition implements FilterInterface
{
    public function __construct(
        private readonly TagConfigurationParser $parser,
    ) {}

    public function getType(): string
    {
        return 'tagCondition';
    }

    /**
     * @param array<string, mixed> $filterItem
     * @return array<int, \CmsIg\Seal\Search\Condition\EqualCondition|\CmsIg\Seal\Search\Condition\SearchCondition|\CmsIg\Seal\Search\Condition\GeoDistanceCondition>
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
            $parsedBody = $request->getParsedBody();
            $selectedValues = is_array($parsedBody) ? ($parsedBody['tx_seal_search'][$filterName] ?? []) : [];
        }

        if (!is_array($selectedValues)) {
            $selectedValues = [];
        }

        $validValues = array_intersect($selectedValues, $allowedValues);

        $conditions = [];
        foreach ($validValues as $value) {
            $conditions[] = Condition::equal('tags', $value);
        }

        return $conditions;
    }
}
