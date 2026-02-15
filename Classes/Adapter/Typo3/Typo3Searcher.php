<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Adapter\Typo3;

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Marshaller\FlattenMarshaller;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Search\Facet\CountFacet;
use CmsIg\Seal\Search\Facet\MinMaxFacet;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\Search;
use CmsIg\Seal\Search\Condition;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;

class Typo3Searcher implements SearcherInterface
{
    private readonly FlattenMarshaller $marshaller;

    public function __construct(private Typo3AdapterHelper $adapterHelper)
    {
        $this->marshaller = new FlattenMarshaller(fieldSeparator: '_');
    }


    public function search(Search $search): Result
    {
        $queryBuilder = $this->adapterHelper->getQueryBuilder($search->index);
        $queryBuilder->from($this->adapterHelper->getTableName($search->index));

        /** @var array<int, Condition\AndCondition|Condition\OrCondition|Condition\EqualCondition|Condition\NotEqualCondition|Condition\GreaterThanCondition|Condition\GreaterThanEqualCondition|Condition\LessThanCondition|Condition\LessThanEqualCondition|Condition\InCondition|Condition\NotInCondition|Condition\IdentifierCondition|Condition\SearchCondition|Condition\GeoDistanceCondition> $searchFilters */
        $searchFilters = $search->filters;
        $filters = $this->recursiveResolveFilterConditions($search->index, $searchFilters, $queryBuilder->expr());

        if (!empty($filters)) {
            $queryBuilder->where($queryBuilder->expr()->and(...$filters));
        }

        $countQueryBuilder = $this->adapterHelper->getQueryBuilder($search->index);
        $countQueryBuilder->from($this->adapterHelper->getTableName($search->index));
        if (!empty($filters)) {
            $countQueryBuilder->where($countQueryBuilder->expr()->and(...$this->recursiveResolveFilterConditions($search->index, $searchFilters, $countQueryBuilder->expr())));
        }
        $countRow = $countQueryBuilder->count('*')->executeQuery()->fetchAssociative();
        $count = (int) ($countRow !== false ? $countRow['COUNT(*)'] : 0);

        $queryBuilder->select('*');
        if (0 !== $search->offset) {
            $queryBuilder->setFirstResult($search->offset);
        }

        if ($search->limit) {
            $queryBuilder->setMaxResults($search->limit);
        }

        foreach ($search->sortBys as $field => $direction) {
            $queryBuilder->addOrderBy($field, $direction);
        }

        return new Result(
            $this->hitsDocuments($search->index, $queryBuilder->executeQuery()->iterateAssociative()),
            $count,
            $this->formatFacets(array_filter($search->facets, static fn($f) => $f instanceof CountFacet || $f instanceof MinMaxFacet), $this->adapterHelper->getTableName($search->index), $filters), // @todo add result information
        );

    }

    /**
     * @param Index $index
     * @param iterable<array<string, mixed>> $hits
     *
     * @return \Generator<int, array<string, mixed>>
     */
    private function hitsDocuments(Index $index, iterable $hits): \Generator
    {
        foreach ($hits as $hit) {
            if (isset($hit['location_latitude']) || isset($hit['location_longitude'])) {
                $hit['location'] = [
                    'latitude' => (float) ($hit['location_latitude'] ?? 0.0),
                    'longitude' => (float) ($hit['location_longitude'] ?? 0.0),
                ];
                unset($hit['location_latitude'], $hit['location_longitude']);
            }

            yield $this->marshaller->unmarshall($index->fields, $hit);
        }
    }

    public function count(Index $index): int
    {
        $tableName = $this->adapterHelper->getTableName($index);
        return $this->adapterHelper->getConnection()->count('*', $tableName, []);
    }


    /**
     * Based on the Loupe integration.
     *
     * @param array<int, Condition\AndCondition|Condition\OrCondition|Condition\EqualCondition|Condition\NotEqualCondition|Condition\GreaterThanCondition|Condition\GreaterThanEqualCondition|Condition\LessThanCondition|Condition\LessThanEqualCondition|Condition\InCondition|Condition\NotInCondition|Condition\IdentifierCondition|Condition\SearchCondition|Condition\GeoDistanceCondition|object> $conditions
     * @return array<int, string|\TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression>
     */
    private function recursiveResolveFilterConditions(Index $index, array $conditions, ExpressionBuilder $expressionBuilder): array
    {
        $filters = [];
        foreach ($conditions as $filter) {
            match (true) {
                $filter instanceof Condition\IdentifierCondition => $filters[] = $expressionBuilder->eq($index->getIdentifierField()->name, $this->escapeFilterValue($filter->identifier)),
                $filter instanceof Condition\SearchCondition => $filters[] = $expressionBuilder->or(
                    $expressionBuilder->like('title', $expressionBuilder->literal('%' . $this->escapeLikeValue($filter->query) . '%')),
                    $expressionBuilder->like('content', $expressionBuilder->literal('%' . $this->escapeLikeValue($filter->query) . '%')),
                ),
                $filter instanceof Condition\EqualCondition => $filters[] = $expressionBuilder->eq($filter->field, $this->escapeFilterValue($filter->value)),
                $filter instanceof Condition\NotEqualCondition => $filters[] = $expressionBuilder->neq($filter->field, $this->escapeFilterValue($filter->value)),
                $filter instanceof Condition\GreaterThanCondition => $filters[] = $expressionBuilder->gt($filter->field, $this->escapeFilterValue($filter->value)),
                $filter instanceof Condition\GreaterThanEqualCondition => $filters[] = $expressionBuilder->gte($filter->field, $this->escapeFilterValue($filter->value)),
                $filter instanceof Condition\LessThanCondition => $filters[] = $expressionBuilder->lt($filter->field, $this->escapeFilterValue($filter->value)),
                $filter instanceof Condition\LessThanEqualCondition => $filters[] = $expressionBuilder->lte($filter->field, $this->escapeFilterValue($filter->value)),
                $filter instanceof Condition\InCondition => $filters[] = $expressionBuilder->in($filter->field, \array_map(fn($value) => $this->escapeFilterValue($value), $filter->values)),
                $filter instanceof Condition\NotInCondition => $filters[] = $expressionBuilder->notIn($filter->field, \array_map(fn($value) => $this->escapeFilterValue($value), $filter->values)),
                $filter instanceof Condition\AndCondition => $filters[] = $expressionBuilder->and(...$this->recursiveResolveFilterConditions($index, $filter->conditions, $expressionBuilder)),
                $filter instanceof Condition\OrCondition => $filters[] = $expressionBuilder->or(...$this->recursiveResolveFilterConditions($index, $filter->conditions, $expressionBuilder)),
                $filter instanceof Condition\GeoDistanceCondition => $filters[] = $this->buildGeoDistanceExpression($filter),
                default => throw new \LogicException('Unsupported filter condition type: ' . $filter::class),
            };
        }

        return $filters;
    }

    private function escapeLikeValue(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    /**
     * Builds a Haversine SQL WHERE clause for geo-distance filtering.
     *
     * Uses the Haversine formula to calculate the great-circle distance
     * between the search point and stored coordinates, returning only
     * documents within the specified radius.
     */
    private function buildGeoDistanceExpression(Condition\GeoDistanceCondition $condition): string
    {
        $latField = $condition->field . '_latitude';
        $lngField = $condition->field . '_longitude';
        $lat = $condition->latitude;
        $lng = $condition->longitude;
        $distance = $condition->distance;

        return \sprintf(
            '6371000 * ACOS('
            . 'COS(RADIANS(%s)) * COS(RADIANS(%s))'
            . ' * COS(RADIANS(%s) - RADIANS(%s))'
            . ' + SIN(RADIANS(%s)) * SIN(RADIANS(%s))'
            . ') <= %s',
            $lat,
            $latField,
            $lngField,
            $lng,
            $lat,
            $latField,
            $distance,
        );
    }

    private function escapeFilterValue(mixed $value): string
    {
        return match (true) {
            \is_bool($value) => $value ? 'true' : 'false',
            \is_int($value), \is_float($value) => (string) $value,
            default => $this->adapterHelper->getConnection()->quote($value),
        };
    }
    /**
     * @param array<int, CountFacet|MinMaxFacet> $facets
     * @param array<int, string|\TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression> $filters
     * @return array<string, array<string, mixed>>
     */
    private function formatFacets(array $facets, string $tableName, array $filters): array
    {
        $formatted = [];

        foreach ($facets as $facet) {
            if ($facet instanceof CountFacet) {
                $formatted[$facet->field]['count'] = $this->computeCountFacet($facet->field, $tableName, $filters);
            }
        }

        return $formatted;
    }

    /**
     * @param array<int, string|\TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression> $filters
     * @return array<string, int>
     */
    private function computeCountFacet(string $field, string $tableName, array $filters): array
    {
        $connection = $this->adapterHelper->getConnection();
        $tagTable = $tableName . '_tag';
        $mmTable = $tableName . '_mm_tag';

        $qb = $connection->createQueryBuilder();
        $qb->select('t.title')
            ->addSelectLiteral('COUNT(*) AS cnt')
            ->from($tagTable, 't')
            ->join('t', $mmTable, 'mm', 'mm.uid_foreign = t.uid')
            ->join('mm', $tableName, 'p', 'p.uid = mm.uid_local');

        if (!empty($filters)) {
            $qb->where($qb->expr()->and(...$filters));
        }

        $qb->groupBy('t.title');

        $counts = [];
        foreach ($qb->executeQuery()->iterateAssociative() as $row) {
            $counts[(string) $row['title']] = (int) $row['cnt'];
        }

        return $counts;
    }
}
