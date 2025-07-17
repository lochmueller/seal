<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Adapter\Typo3;

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Marshaller\Marshaller;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\Search;
use CmsIg\Seal\Search\Condition;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;

class Typo3Searcher implements SearcherInterface
{
    private readonly Marshaller $marshaller;

    public function __construct(private Typo3AdapterHelper $adapterHelper)
    {
        $this->marshaller = new Marshaller();
    }


    public function search(Search $search): Result
    {
        $queryBuilder = $this->adapterHelper->getQueryBuilder($search->index);
        $queryBuilder->select('*')
            ->from($this->adapterHelper->getTableName($search->index));

        $filters = $this->recursiveResolveFilterConditions($search->index, $search->filters, true, $queryBuilder->expr());

        if ('' !== $filters) {
            $queryBuilder->where($filters);
        }

        if (0 !== $search->offset) {
            $queryBuilder->setFirstResult($search->offset);
        }

        if ($search->limit) {
            $queryBuilder->setMaxResults($search->limit);
        }

        if ($search->distinct) {
            #$searchParameters = $searchParameters->withDistinct($search->distinct);
        }

        if ([] !== $search->highlightFields) {
            #$searchParameters = $searchParameters->withAttributesToHighlight(
            #    $search->highlightFields,
            #    $search->highlightPreTag,
            #    $search->highlightPostTag,
            #);
        }


        $sorts = [];
        foreach ($search->sortBys as $field => $direction) {
            #$sorts[] = $this->loupeHelper->formatField($field) . ':' . $direction;
        }

        if ([] !== $sorts) {
            #$searchParameters = $searchParameters->withSort($sorts);
        }


        // @todo handle Site


        return new Result(
            $this->hitsToDocuments($search->index, $queryBuilder->executeQuery()->iterateAssociative()),
            1,
        );

    }

    /**
     * @param Index $index
     * @param iterable<array<string, mixed>> $hits
     *
     * @return \Generator<int, array<string, mixed>>
     */
    private function hitsToDocuments(Index $index, iterable $hits): \Generator
    {
        foreach ($hits as $hit) {
            yield $this->marshaller->unmarshall($index->fields, $hit);
        }
    }

    public function count(Index $index): int
    {
        $tableName = $this->adapterHelper->getTableName($index);
        return $this->adapterHelper->getConnection()->count('*', $tableName, []);
    }


    /**
     * @param object[] $conditions
     */
    private function recursiveResolveFilterConditions(Index $index, array $conditions, bool $conjunctive, ExpressionBuilder $expressionBuilder): string
    {
        // @todo migrate to expression build

        // $index->searchableFields @todo for like query

        $filters = [];

        foreach ($conditions as $filter) {
            match (true) {

                $filter instanceof Condition\IdentifierCondition => $filters[] = $index->getIdentifierField()->name . ' = ' . $this->escapeFilterValue($filter->identifier),
                $filter instanceof Condition\SearchCondition => $filters[] = $expressionBuilder->like('content', $expressionBuilder->literal('%' . $filter->query . '%')),
                $filter instanceof Condition\EqualCondition => $filters[] = $expressionBuilder->eq($filter->field, $expressionBuilder->literal($filter->value)),
                $filter instanceof Condition\NotEqualCondition => $filters[] = $filter->field . ' != ' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\GreaterThanCondition => $filters[] = $filter->field . ' > ' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\GreaterThanEqualCondition => $filters[] = $filter->field . ' >= ' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\LessThanCondition => $filters[] = $filter->field . ' < ' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\LessThanEqualCondition => $filters[] = $filter->field . ' <= ' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\InCondition => $filters[] = $filter->field . ' IN (' . \implode(', ', \array_map(fn($value) => $this->escapeFilterValue($value), $filter->values)) . ')',
                $filter instanceof Condition\NotInCondition => $filters[] = $filter->field . ' NOT IN (' . \implode(', ', \array_map(fn($value) => $this->escapeFilterValue($value), $filter->values)) . ')',
                $filter instanceof Condition\GeoDistanceCondition => $filters[] = \sprintf(
                    '_geoRadius(%s, %s, %s, %s)',
                    $filter->field,
                    $filter->latitude,
                    $filter->longitude,
                    $filter->distance,
                ),
                $filter instanceof Condition\GeoBoundingBoxCondition => $filters[] = \sprintf(
                    '_geoBoundingBox(%s, %s, %s, %s, %s)',
                    $filter->field,
                    $filter->northLatitude,
                    $filter->eastLongitude,
                    $filter->southLatitude,
                    $filter->westLongitude,
                ),
                $filter instanceof Condition\AndCondition => $filters[] = '(' . $this->recursiveResolveFilterConditions($index, $filter->conditions, true, $expressionBuilder) . ')',
                $filter instanceof Condition\OrCondition => $filters[] = '(' . $this->recursiveResolveFilterConditions($index, $filter->conditions, false, $expressionBuilder) . ')',
                default => throw new \LogicException($filter::class . ' filter not implemented.'),
            };
        }

        if (\count($filters) < 2) {
            return \implode('', $filters);
        }

        return \implode($conjunctive ? ' AND ' : ' OR ', $filters);
    }

    private function escapeFilterValue(mixed $value): string
    {
        return match (true) {
            \is_bool($value) => $value ? 'true' : 'false',
            \is_int($value), \is_float($value) => (string) $value,
            default => $this->adapterHelper->getConnection()->quote($value),
        };
    }
}
