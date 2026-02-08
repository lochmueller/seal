<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Filter;

use CmsIg\Seal\Search\Condition\Condition;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

class SearchCondition implements FilterInterface
{
    public function getType(): string
    {
        return 'searchCondition';
    }

    /**
     * @param array<string, mixed> $filterItem
     * @return array<int, \CmsIg\Seal\Search\Condition\EqualCondition|\CmsIg\Seal\Search\Condition\SearchCondition|\CmsIg\Seal\Search\Condition\GeoDistanceCondition>
     */
    /**
     * @param array<string, mixed> $filterItem
     * @return array<int, \CmsIg\Seal\Search\Condition\EqualCondition|\CmsIg\Seal\Search\Condition\SearchCondition|\CmsIg\Seal\Search\Condition\GeoDistanceCondition>
     */
    public function getFilterConfiguration(array $filterItem, RequestInterface $request): array
    {
        $search = '';
        if ($request instanceof ServerRequestInterface) {
            $parsedBody = $request->getParsedBody();
            $search = is_array($parsedBody) ? ($parsedBody['tx_seal_search']['search'] ?? '') : '';
        }
        if ($search === '') {
            return [];
        }
        return [Condition::search($search)];
    }
}
