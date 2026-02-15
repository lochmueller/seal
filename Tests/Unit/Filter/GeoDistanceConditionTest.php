<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Filter;

use Lochmueller\Seal\Filter\GeoDistanceCondition;
use Lochmueller\Seal\Filter\RadiusConfigurationParser;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Unit tests for GeoDistanceCondition edge cases.
 */
class GeoDistanceConditionTest extends AbstractTest
{
    private GeoDistanceCondition $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new GeoDistanceCondition(new RadiusConfigurationParser());
    }

    public function testGetTypeReturnsGeoDistanceCondition(): void
    {
        self::assertSame('geoDistanceCondition', $this->subject->getType());
    }

    public function testMissingLatReturnsEmptyArray(): void
    {
        $filterItem = ['radius_steps' => "10=10 km\n25=25 km"];
        $request = $this->createStubServerRequest([
            'geo_position_lng' => '11.5820',
            'geo_position_radius' => '10',
        ]);

        $conditions = $this->subject->getFilterConfiguration($filterItem, $request);

        self::assertSame([], $conditions);
    }

    public function testMissingLngReturnsEmptyArray(): void
    {
        $filterItem = ['radius_steps' => "10=10 km\n25=25 km"];
        $request = $this->createStubServerRequest([
            'geo_position_lat' => '48.1351',
            'geo_position_radius' => '10',
        ]);

        $conditions = $this->subject->getFilterConfiguration($filterItem, $request);

        self::assertSame([], $conditions);
    }

    public function testMissingRadiusReturnsEmptyArray(): void
    {
        $filterItem = ['radius_steps' => "10=10 km\n25=25 km"];
        $request = $this->createStubServerRequest([
            'geo_position_lat' => '48.1351',
            'geo_position_lng' => '11.5820',
        ]);

        $conditions = $this->subject->getFilterConfiguration($filterItem, $request);

        self::assertSame([], $conditions);
    }

    public function testMissingAllCoordinatesReturnsEmptyArray(): void
    {
        $filterItem = ['radius_steps' => "10=10 km\n25=25 km"];
        $request = $this->createStubServerRequest([]);

        $conditions = $this->subject->getFilterConfiguration($filterItem, $request);

        self::assertSame([], $conditions);
    }

    public function testRadiusNotInConfiguredStepsReturnsEmptyArray(): void
    {
        $filterItem = ['radius_steps' => "10=10 km\n25=25 km\n50=50 km"];
        $request = $this->createStubServerRequest([
            'geo_position_lat' => '48.1351',
            'geo_position_lng' => '11.5820',
            'geo_position_radius' => '30',
        ]);

        $conditions = $this->subject->getFilterConfiguration($filterItem, $request);

        self::assertSame([], $conditions);
    }

    public function testRequestWithoutParsedBodyReturnsEmptyArray(): void
    {
        $filterItem = ['radius_steps' => "10=10 km\n25=25 km"];
        $request = $this->createStub(RequestInterface::class);

        $conditions = $this->subject->getFilterConfiguration($filterItem, $request);

        self::assertSame([], $conditions);
    }

    /**
     * Creates a stub ServerRequestInterface with the given geo data under tx_seal_search.
     *
     * @param array<string, string> $geoData
     */
    private function createStubServerRequest(array $geoData): ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([
            'tx_seal_search' => $geoData,
        ]);

        return $request;
    }
}
