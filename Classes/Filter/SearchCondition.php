<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Filter;

use Psr\Http\Message\RequestInterface;
use CmsIg\Seal\Search\Condition\Condition;

class SearchCondition implements FilterInterface
{
    public function getType(): string
    {
        return 'searchCondition';
    }

    public function getFilterConfiguration(array $filterItem, RequestInterface $request): array
    {
        $search = $request->getParsedBody()['tx_seal_search']['search'] ?? '';
        return [Condition::search($search)];
    }
}
