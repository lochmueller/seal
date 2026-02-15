<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Filter;

use CmsIg\Seal\Search\Condition\GeoDistanceCondition as SealGeoDistanceCondition;
use Lochmueller\Seal\Filter\GeoDistanceCondition;
use Lochmueller\Seal\Filter\RadiusConfigurationParser;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Feature: geo-distance-filter, Property 2, Property 3
 */
class GeoDistanceConditionPropertyTest extends AbstractTest
{
    private GeoDistanceCondition $subject;

    private RadiusConfigurationParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new RadiusConfigurationParser();
        $this->subject = new GeoDistanceCondition($this->parser);
    }

    /**
     * Feature: geo-distance-filter, Property 2
     *
     * For all valid combinations of latitude ([-90, 90]), longitude ([-180, 180])
     * and a radius that is contained in the configured radius steps, the filter
     * shall return exactly one GeoDistanceCondition whose distance equals
     * radius * 1000 (meters), whose latitude and longitude match the input
     * values, and whose field is 'location'.
     */
    public function testValidGeoInputsProduceCorrectCondition(): void
    {
        for ($i = 0; $i < self::TEST_ITERATIONS; $i++) {
            $configuredRadii = $this->generateRandomRadiusSteps();
            $configurationString = $this->parser->format($configuredRadii);
            $selectedEntry = $configuredRadii[array_rand($configuredRadii)];
            $selectedRadius = $selectedEntry['value'];
            $lat = $this->randomLatitude();
            $lng = $this->randomLongitude();

            $filterItem = ['radius_steps' => $configurationString];
            $request = $this->createGeoRequest($lat, $lng, $selectedRadius);
            $conditions = $this->subject->getFilterConfiguration($filterItem, $request);

            self::assertCount(1, $conditions, 'Iteration ' . $i . ': exactly one condition expected');

            $condition = $conditions[0];
            self::assertInstanceOf(SealGeoDistanceCondition::class, $condition);
            self::assertSame('location', $condition->field, 'Iteration ' . $i);
            self::assertSame($lat, $condition->latitude, 'Iteration ' . $i);
            self::assertSame($lng, $condition->longitude, 'Iteration ' . $i);
            self::assertSame($selectedRadius * 1000, $condition->distance, 'Iteration ' . $i);
        }
    }

    /**
     * Feature: geo-distance-filter, Property 3
     */
    public function testInvalidCoordinatesProduceEmptyResult(): void
    {
        for ($i = 0; $i < self::TEST_ITERATIONS; $i++) {
            $configuredRadii = $this->generateRandomRadiusSteps();
            $configurationString = $this->parser->format($configuredRadii);
            $selectedEntry = $configuredRadii[array_rand($configuredRadii)];
            $selectedRadius = $selectedEntry['value'];

            // Generate coordinates where at least one is outside valid range
            [$lat, $lng] = $this->generateInvalidCoordinates();

            $filterItem = ['radius_steps' => $configurationString];
            $request = $this->createGeoRequest($lat, $lng, $selectedRadius);
            $conditions = $this->subject->getFilterConfiguration($filterItem, $request);

            self::assertSame(
                [],
                $conditions,
                sprintf(
                    'Iteration %d: expected empty array for invalid coordinates lat=%f, lng=%f, radius=%d',
                    $i,
                    $lat,
                    $lng,
                    $selectedRadius,
                ),
            );
        }
    }

    /**
     * Generate coordinates where at least lat is outside [-90, 90] or lng is outside [-180, 180].
     *
     * @return array{0: float, 1: float}
     */
    private function generateInvalidCoordinates(): array
    {
        // Decide which coordinate(s) to make invalid: 0 = lat only, 1 = lng only, 2 = both
        $invalidChoice = random_int(0, 2);

        if ($invalidChoice === 0 || $invalidChoice === 2) {
            // Invalid latitude: outside [-90, 90]
            $lat = random_int(0, 1) === 0
                ? -(random_int(901, 9000) / 10.0)   // -900.0 .. -90.1
                : random_int(901, 9000) / 10.0;      // 90.1 .. 900.0
        } else {
            // Valid latitude
            $lat = $this->randomLatitude();
        }

        if ($invalidChoice === 1 || $invalidChoice === 2) {
            // Invalid longitude: outside [-180, 180]
            $lng = random_int(0, 1) === 0
                ? -(random_int(1801, 18000) / 10.0)  // -1800.0 .. -180.1
                : random_int(1801, 18000) / 10.0;     // 180.1 .. 1800.0
        } else {
            // Valid longitude
            $lng = $this->randomLongitude();
        }

        return [$lat, $lng];
    }


    private function createGeoRequest(float $lat, float $lng, int $radius): ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([
            'tx_seal_search' => [
                'geo_position_lat' => (string) $lat,
                'geo_position_lng' => (string) $lng,
                'geo_position_radius' => (string) $radius,
            ],
        ]);

        return $request;
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function generateRandomRadiusSteps(): array
    {
        $count = random_int(1, 8);
        $radii = [];
        $usedValues = [];

        for ($j = 0; $j < $count; $j++) {
            $value = random_int(1, 500);
            if (in_array($value, $usedValues, true)) {
                continue;
            }
            $usedValues[] = $value;
            $label = random_int(0, 1) === 0 ? $value . ' km' : (string) $value;
            $radii[] = ['value' => $value, 'label' => $label];
        }

        if ($radii === []) {
            $radii[] = ['value' => 10, 'label' => '10 km'];
        }

        return $radii;
    }

    private function randomLatitude(): float
    {
        return random_int(-900000, 900000) / 10000.0;
    }

    private function randomLongitude(): float
    {
        return random_int(-1800000, 1800000) / 10000.0;
    }
}
