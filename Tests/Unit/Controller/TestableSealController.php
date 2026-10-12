<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Controller;

use CmsIg\Seal\Search\SearchBuilder;
use Lochmueller\Seal\Controller\AbstractSealController;

/**
 * Concrete controller that exposes the protected helper methods for testing.
 */
class TestableSealController extends AbstractSealController
{
    /**
     * @return array<string, mixed>
     */
    public function callGetCurrentContentElementRow(): array
    {
        return $this->getCurrentContentElementRow();
    }

    public function callBuildSealId(int $contentElementUid): string
    {
        return $this->buildSealId($contentElementUid);
    }

    /**
     * @return iterable<array<string, mixed>>
     */
    public function callGetFilterRowsByContentElementUid(int $uid): iterable
    {
        return $this->getFilterRowsByContentElementUid($uid);
    }

    /**
     * @param array<int, array<string, mixed>> $filterRows
     * @param array<string, mixed> $requestData
     * @param array<string, int> $tagFacetCounts
     * @return array<int, array<string, mixed>>
     */
    public function callAddCalculatedValuesForFilterRows(array $filterRows, array $requestData = [], array $tagFacetCounts = []): array
    {
        return $this->addCalculatedValuesForFilterRows($filterRows, $requestData, $tagFacetCounts);
    }

    public function callGetSearchBuilder(): SearchBuilder
    {
        return $this->getSearchBuilder();
    }
}
