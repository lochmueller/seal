<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Filter;

use CmsIg\Seal\Search\Condition\Condition;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

class GeoDistanceCondition implements FilterInterface
{
    public function __construct(
        private readonly RadiusConfigurationParser $parser,
    ) {}

    public function getType(): string
    {
        return 'geoDistanceCondition';
    }

    /**
     * @param array<string, mixed> $filterItem
     * @return array<int, \CmsIg\Seal\Search\Condition\EqualCondition|\CmsIg\Seal\Search\Condition\SearchCondition|\CmsIg\Seal\Search\Condition\GeoDistanceCondition>
     */
    public function getFilterConfiguration(array $filterItem, RequestInterface $request): array
    {
        if (!$request instanceof ServerRequestInterface) {
            return [];
        }

        $parsedBody = $request->getParsedBody();
        $searchData = is_array($parsedBody) ? ($parsedBody['tx_seal_search'] ?? []) : [];

        if (!is_array($searchData)) {
            return [];
        }

        $latRaw = $searchData['geo_position_lat'] ?? null;
        $lngRaw = $searchData['geo_position_lng'] ?? null;
        $radiusRaw = $searchData['geo_position_radius'] ?? null;

        if ($latRaw === null || $latRaw === '' || $lngRaw === null || $lngRaw === '' || $radiusRaw === null || $radiusRaw === '') {
            return [];
        }

        if (!is_numeric($latRaw) || !is_numeric($lngRaw) || !is_numeric($radiusRaw)) {
            return [];
        }

        $lat = (float) $latRaw;
        $lng = (float) $lngRaw;
        $radius = (int) $radiusRaw;

        if ($lat < -90.0 || $lat > 90.0) {
            return [];
        }

        if ($lng < -180.0 || $lng > 180.0) {
            return [];
        }

        $configuredRadii = $this->parser->parse((string) ($filterItem['radius_steps'] ?? ''));
        $allowedValues = array_map(
            static fn(array $entry): int => $entry['value'],
            $configuredRadii,
        );

        if (!in_array($radius, $allowedValues, true)) {
            return [];
        }

        $meters = $radius * 1000;

        return [Condition::geoDistance('location', $lat, $lng, $meters)];
    }
}
