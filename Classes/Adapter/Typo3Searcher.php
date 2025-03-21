<?php

namespace Seal\Adapter;

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Marshaller\Marshaller;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\Search;

class Typo3Searcher implements SearcherInterface
{
    private readonly Marshaller $marshaller;
    public function __construct()
    {
        $this->marshaller = new Marshaller();
    }


    public function search(Search $search): Result
    {

        $this->marshaller->marshall($search);
    }
}
