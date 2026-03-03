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

        $lat = (float)($searchData['geo_position_lat'] ?? 0.0);
        $lng = (float)($searchData['geo_position_lng'] ?? 0.0);
        $radius = (int)($searchData['geo_position_radius'] ?? 0);

        if ($lat === 0.0 || $lng === 0.0 || $radius === 0) {
            return [];
        }

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

        return [Condition::geoDistance('location', $lat, $lng, $radius * 1000)];
    }
}
