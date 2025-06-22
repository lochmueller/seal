<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Adapter;

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Marshaller\Marshaller;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Search\Condition\SearchCondition;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\Search;

class Typo3Searcher implements SearcherInterface
{
    private readonly Marshaller $marshaller;
    public function __construct(private Typo3AdapterHelper $adapterHelper)
    {
        $this->marshaller = new Marshaller();
    }


    public function search(Search $search): Result
    {


        #$connection = $this->adapterHelper->getConnection();

        // @todo handle search
        foreach ($search->filters as $filter) {
            if ($filter instanceof SearchCondition) {

            }
        }

        // @todo handle Site


        return new Result(
            $this->hitsToDocuments($search->index, [['title' => 'test']]),
            1,
        );

    }

    /**
     * @param Index $index
     * @param iterable<array<string, mixed>> $hits
     *
     * @return \Generator<int, array<string, mixed>>
     */
    private function hitsToDocuments(Index $index, array $hits): \Generator
    {
        foreach ($hits as $hit) {
            yield $this->marshaller->unmarshall($index->fields, $hit);
        }
    }
}
