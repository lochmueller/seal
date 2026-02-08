<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Filter;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

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
        $values = [];
        if ($request instanceof ServerRequestInterface) {
            $parsedBody = $request->getParsedBody();
            $values = is_array($parsedBody) ? ($parsedBody['tx_seal_search'][$filterName] ?? []) : [];
        }

        // @todo
        #DebuggerUtility::var_dump($values);die();
        #return [Condition::in($search)];

        // @todo implement
        return [];
    }
}
