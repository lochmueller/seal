<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Pagination;

use CmsIg\Seal\Search\Result;
use TYPO3\CMS\Core\Pagination\AbstractPaginator;

class SearchResultArrayPaginator extends AbstractPaginator
{
    public int $localItemsPerPage = 0;
    private ?iterable $resultCached = null;

    public function __construct(
        protected Result $result,
        int              $currentPageNumber = 1,
        int              $itemsPerPage = 10,
    ) {
        $this->setCurrentPageNumber($currentPageNumber);
        $this->localItemsPerPage = $itemsPerPage;
        $this->setItemsPerPage($itemsPerPage);

        $this->updateInternalState();
    }

    /**
     * @return iterable<array<string, mixed>>
     */
    public function getPaginatedItems(): iterable
    {
        if ($this->resultCached === null) {
            $this->resultCached = iterator_to_array($this->result);
        }

        yield from $this->resultCached;
    }


    public function getTotalAmountOfItems(): int
    {
        return $this->result->total();
    }

    protected function getAmountOfItemsOnCurrentPage(): int
    {
        $total = $this->getTotalAmountOfItems();

        if ($total === 0) {
            return 0;
        }

        $remainingItems = $total - ($this->localItemsPerPage * ($this->getCurrentPageNumber() - 1));

        return min($this->localItemsPerPage, max(0, $remainingItems));
    }

    protected function updatePaginatedItems(int $itemsPerPage, int $offset): void
    {
        // already the right part
    }
}
