<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Pagination;

use CmsIg\Seal\Search\Result;
use TYPO3\CMS\Core\Pagination\AbstractPaginator;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;

class SearchResultArrayPaginator extends AbstractPaginator
{
    private iterable $paginatedItems = [];
    private int $myItemsPerPage  = 10;

    public function __construct(
        protected Result $result,
        int              $currentPageNumber = 1,
        int              $itemsPerPage = 10,
    ) {
        $this->setCurrentPageNumber($currentPageNumber);
        $this->myItemsPerPage = $itemsPerPage;
        $this->setItemsPerPage($itemsPerPage);

        $this->updateInternalState();
    }

    public function getPaginatedItems(): iterable
    {
        yield from $this->result;
    }


    protected function getTotalAmountOfItems(): int
    {
        return $this->result->total();
    }

    protected function getAmountOfItemsOnCurrentPage(): int
    {
        return $this->myItemsPerPage ; // @todo handle
    }

    protected function updatePaginatedItems(int $itemsPerPage, int $offset): void
    {
        // already the right part
    }
}
