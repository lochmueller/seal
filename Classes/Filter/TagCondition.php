<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Filter;

use Psr\Http\Message\RequestInterface;

class TagCondition implements FilterInterface
{
    public function getType(): string
    {
        return 'tagCondition';
    }

    /**
     * @param array<string, mixed> $filterItem
     * @return array<int, \CmsIg\Seal\Search\Condition\SearchCondition>
     */
    public function getFilterConfiguration(array $filterItem, RequestInterface $request): array
    {
        $filterName = 'field_' . $filterItem['uid'];
        $values = $request->getParsedBody()['tx_seal_search'][$filterName] ?? [];

        // @todo
        #DebuggerUtility::var_dump($values);die();
        #return [Condition::in($search)];

        // @todo implement
        return [];
    }
}
