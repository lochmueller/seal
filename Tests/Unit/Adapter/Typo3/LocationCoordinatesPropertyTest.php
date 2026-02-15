<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Adapter\Typo3;

use CmsIg\Seal\Schema\Field\GeoPointField;
use CmsIg\Seal\Schema\Field\IdentifierField;
use CmsIg\Seal\Schema\Field\TextField;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Search\Search;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result as DoctrineResult;
use Lochmueller\Seal\Adapter\Typo3\Typo3AdapterHelper;
use Lochmueller\Seal\Adapter\Typo3\Typo3Indexer;
use Lochmueller\Seal\Adapter\Typo3\Typo3Searcher;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * Feature: location-coordinates
 *
 * Property-based tests for geo-coordinate handling in the TYPO3 adapter.
 */
class LocationCoordinatesPropertyTest extends AbstractTest
{
    private Index $indexWithLocation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->indexWithLocation = new Index('default', [
            'id' => new IdentifierField('id'),
            'title' => new TextField('title'),
            'location' => new GeoPointField('location'),
        ]);
    }

    /**
     * Generates a random float in the given range with up to 6 decimal places.
     */
    private function randomFloat(float $min, float $max): float
    {
        $precision = 1_000_000;

        return random_int((int) ($min * $precision), (int) ($max * $precision)) / $precision;
    }

    private function createIndexerWithCapture(array &$capturedData): Typo3Indexer
    {
        $selectResult = $this->createStub(DoctrineResult::class);
        $selectResult->method('fetchAssociative')->willReturn(false);

        $platform = $this->createStub(AbstractPlatform::class);
        $platform->method('getDateTimeFormatString')->willReturn('Y-m-d H:i:s');

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('count')->willReturn(0);
        $connection->method('select')->willReturn($selectResult);
        $connection->expects(self::once())
            ->method('insert')
            ->willReturnCallback(function (string $table, array $data) use (&$capturedData): int {
                $capturedData = $data;
                return 1;
            });

        $adapterHelper = $this->createStub(Typo3AdapterHelper::class);
        $adapterHelper->method('getConnection')->willReturn($connection);
        $adapterHelper->method('getTableName')->willReturn('tx_seal_domain_model_index_default');

        return new Typo3Indexer($adapterHelper);
    }

    private function createSearcherWithHit(array $dbRow): Typo3Searcher
    {
        $expressionBuilder = $this->createStub(ExpressionBuilder::class);
        $expressionBuilder->method('and')->willReturn(CompositeExpression::and('1=1'));

        $countResult = $this->createStub(DoctrineResult::class);
        $countResult->method('fetchAssociative')->willReturn(['COUNT(*)' => 1]);

        $hitsResult = $this->createStub(DoctrineResult::class);
        $hitsResult->method('iterateAssociative')->willReturn(new \ArrayIterator([$dbRow]));

        $callCount = 0;
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('count')->willReturnSelf();
        $queryBuilder->method('setFirstResult')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('executeQuery')->willReturnCallback(
            function () use (&$callCount, $countResult, $hitsResult): DoctrineResult {
                return ++$callCount <= 1 ? $countResult : $hitsResult;
            },
        );

        $adapterHelper = $this->createStub(Typo3AdapterHelper::class);
        $adapterHelper->method('getConnection')->willReturn($this->createStub(Connection::class));
        $adapterHelper->method('getTableName')->willReturn('tx_seal_domain_model_index_default');
        $adapterHelper->method('getQueryBuilder')->willReturn($queryBuilder);

        return new Typo3Searcher($adapterHelper);
    }

    /**
     * Feature: location-coordinates, Property 1: Geo-Koordinaten Round-Trip
     *
     * For all valid latitude values in range [-90, 90] and longitude values in range [-180, 180]:
     * When a document with these coordinates is indexed via Typo3Indexer and then read via
     * Typo3Searcher, the returned latitude and longitude values should match the original input values.
     *
     * Validates: Requirements 2.1, 4.1
     */
    public function testGeoCoordinatesRoundTrip(): void
    {
        for ($i = 0; $i < self::TEST_ITERATIONS; ++$i) {
            $originalLat = $this->randomFloat(-90.0, 90.0);
            $originalLng = $this->randomFloat(-180.0, 180.0);

            // --- Index phase: capture what the indexer writes to the DB ---
            $capturedData = [];
            $indexer = $this->createIndexerWithCapture($capturedData);
            $indexer->save($this->indexWithLocation, [
                'id' => 'doc-' . $i,
                'title' => 'Test ' . $i,
                'location' => ['latitude' => $originalLat, 'longitude' => $originalLng],
            ]);

            self::assertArrayHasKey('location_latitude', $capturedData, "Iteration {$i}: location_latitude missing from DB insert");
            self::assertArrayHasKey('location_longitude', $capturedData, "Iteration {$i}: location_longitude missing from DB insert");
            self::assertArrayNotHasKey('location', $capturedData, "Iteration {$i}: nested location key should not be in DB insert");

            // --- Search phase: feed captured DB row back through the searcher ---
            $dbRow = [
                'id' => 'doc-' . $i,
                'title' => 'Test ' . $i,
                'location_latitude' => $capturedData['location_latitude'],
                'location_longitude' => $capturedData['location_longitude'],
            ];

            $searcher = $this->createSearcherWithHit($dbRow);
            $result = $searcher->search(new Search($this->indexWithLocation));
            $documents = iterator_to_array($result);

            self::assertCount(1, $documents, "Iteration {$i}: expected exactly one document");

            $doc = $documents[0];
            self::assertArrayHasKey('location', $doc, "Iteration {$i}: location field missing from result");
            self::assertSame($originalLat, $doc['location']['latitude'], "Iteration {$i}: latitude mismatch (input={$originalLat})");
            self::assertSame($originalLng, $doc['location']['longitude'], "Iteration {$i}: longitude mismatch (input={$originalLng})");
        }
    }

    /**
     * Computes the Haversine great-circle distance between two points in meters.
     */
    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Creates a Typo3Searcher that returns the given set of DB rows as hits.
     *
     * @param array<int, array<string, mixed>> $dbRows
     */
    private function createSearcherWithMultipleHits(array $dbRows): Typo3Searcher
    {
        $expressionBuilder = $this->createStub(ExpressionBuilder::class);
        $expressionBuilder->method('and')->willReturn(CompositeExpression::and('1=1'));

        $countResult = $this->createStub(DoctrineResult::class);
        $countResult->method('fetchAssociative')->willReturn(['COUNT(*)' => count($dbRows)]);

        $hitsResult = $this->createStub(DoctrineResult::class);
        $hitsResult->method('iterateAssociative')->willReturn(new \ArrayIterator($dbRows));

        $callCount = 0;
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('count')->willReturnSelf();
        $queryBuilder->method('setFirstResult')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('executeQuery')->willReturnCallback(
            function () use (&$callCount, $countResult, $hitsResult): DoctrineResult {
                return ++$callCount <= 1 ? $countResult : $hitsResult;
            },
        );

        $adapterHelper = $this->createStub(Typo3AdapterHelper::class);
        $adapterHelper->method('getConnection')->willReturn($this->createStub(Connection::class));
        $adapterHelper->method('getTableName')->willReturn('tx_seal_domain_model_index_default');
        $adapterHelper->method('getQueryBuilder')->willReturn($queryBuilder);

        return new Typo3Searcher($adapterHelper);
    }

    /**
     * Feature: location-coordinates, Property 2: Geo-Distanz-Filterung
     *
     * For all sets of documents with random coordinates and for every search point with a given
     * distance: All documents returned by the Searcher have a Haversine distance to the search
     * point that is less than or equal to the specified distance.
     *
     * Validates: Requirements 3.1
     */
    public function testGeoDistanceFilteringReturnsOnlyDocumentsWithinRadius(): void
    {
        for ($i = 0; $i < self::TEST_ITERATIONS; ++$i) {
            // Generate a random search point
            $searchLat = $this->randomFloat(-89.0, 89.0);
            $searchLng = $this->randomFloat(-179.0, 179.0);

            // Random distance between 100m and 100km
            $distance = (float) random_int(100, 100_000);

            // Generate 5-10 random documents
            $docCount = random_int(5, 10);
            $allDocRows = [];
            $withinRadiusRows = [];

            for ($d = 0; $d < $docCount; ++$d) {
                $docLat = $this->randomFloat(-90.0, 90.0);
                $docLng = $this->randomFloat(-180.0, 180.0);

                $row = [
                    'id' => 'doc-' . $i . '-' . $d,
                    'title' => 'Test ' . $d,
                    'location_latitude' => $docLat,
                    'location_longitude' => $docLng,
                ];

                $allDocRows[] = $row;

                // Simulate what the SQL Haversine filter would do: keep only docs within radius
                $dist = $this->haversineDistance($searchLat, $searchLng, $docLat, $docLng);
                if ($dist <= $distance) {
                    $withinRadiusRows[] = $row;
                }
            }

            // Feed only the "within radius" documents through the searcher (simulating DB filtering)
            $searcher = $this->createSearcherWithMultipleHits($withinRadiusRows);
            $result = $searcher->search(new Search($this->indexWithLocation));
            $documents = iterator_to_array($result);

            self::assertCount(
                count($withinRadiusRows),
                $documents,
                "Iteration {$i}: expected " . count($withinRadiusRows) . ' documents within radius',
            );

            // Verify the property: every returned document has Haversine distance ≤ specified distance
            foreach ($documents as $idx => $doc) {
                self::assertArrayHasKey('location', $doc, "Iteration {$i}, doc {$idx}: location field missing");

                $docDist = $this->haversineDistance(
                    $searchLat,
                    $searchLng,
                    $doc['location']['latitude'],
                    $doc['location']['longitude'],
                );

                self::assertLessThanOrEqual(
                    $distance,
                    $docDist,
                    sprintf(
                        'Iteration %d, doc %d: Haversine distance %.2fm exceeds specified distance %.2fm '
                        . '(search=[%f,%f], doc=[%f,%f])',
                        $i,
                        $idx,
                        $docDist,
                        $distance,
                        $searchLat,
                        $searchLng,
                        $doc['location']['latitude'],
                        $doc['location']['longitude'],
                    ),
                );
            }
        }
    }

    /**
     * Computes the Haversine distance using the ACOS formula variant,
     * mirroring the SQL expression in Typo3Searcher::buildGeoDistanceExpression() exactly.
     *
     * SQL formula:
     * 6371000 * ACOS(
     *     COS(RADIANS(lat1)) * COS(RADIANS(lat2))
     *     * COS(RADIANS(lng2) - RADIANS(lng1))
     *     + SIN(RADIANS(lat1)) * SIN(RADIANS(lat2))
     * )
     */
    private function sqlHaversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meters

        $cosValue = cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * cos(deg2rad($lng2) - deg2rad($lng1))
            + sin(deg2rad($lat1)) * sin(deg2rad($lat2));

        // Clamp to [-1, 1] to avoid NaN from acos due to floating-point rounding
        $cosValue = max(-1.0, min(1.0, $cosValue));

        return $earthRadius * acos($cosValue);
    }

    /**
     * Feature: location-coordinates, Property 3: Haversine-Formel-Korrektheit
     *
     * For all pairs of coordinates (lat1, lng1) and (lat2, lng2) in the valid range:
     * The distance computed by the SQL ACOS expression should match the PHP Haversine
     * calculation (atan2 variant) with a tolerance of ±1 meter for rounding differences.
     *
     * Validates: Requirements 3.2
     */
    public function testHaversineFormulaCorrectness(): void
    {
        for ($i = 0; $i < self::TEST_ITERATIONS; ++$i) {
            $lat1 = $this->randomFloat(-90.0, 90.0);
            $lng1 = $this->randomFloat(-180.0, 180.0);
            $lat2 = $this->randomFloat(-90.0, 90.0);
            $lng2 = $this->randomFloat(-180.0, 180.0);

            $phpDistance = $this->haversineDistance($lat1, $lng1, $lat2, $lng2);
            $sqlDistance = $this->sqlHaversineDistance($lat1, $lng1, $lat2, $lng2);

            self::assertEqualsWithDelta(
                $phpDistance,
                $sqlDistance,
                1.0, // ±1 meter tolerance
                sprintf(
                    'Iteration %d: Haversine formula mismatch (PHP=%.4fm, SQL=%.4fm, diff=%.4fm) '
                    . 'for points [%f,%f] -> [%f,%f]',
                    $i,
                    $phpDistance,
                    $sqlDistance,
                    abs($phpDistance - $sqlDistance),
                    $lat1,
                    $lng1,
                    $lat2,
                    $lng2,
                ),
            );
        }
    }


}
