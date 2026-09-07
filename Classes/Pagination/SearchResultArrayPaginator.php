<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Pagination;

use CmsIg\Seal\Search\Result;
use TYPO3\CMS\Core\Pagination\AbstractPaginator;

class SearchResultArrayPaginator extends AbstractPaginator
{
    public int $localItemsPerPage = 0;
    /**
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $resultCached = null;

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
     * Returns an array and not a generator on purpose: Fluid's f:for ViewHelper calls count()
     * on the value as soon as the "iteration" argument is used, and a generator is not
     * Countable. Caching alone does not help there, because a generator function hands out a
     * fresh - and still uncountable - Generator on every call.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPaginatedItems(): iterable
    {
        if ($this->resultCached === null) {
            // Not preserving the keys: the documents are a result list, and an adapter that
            // yields its own keys would otherwise be able to overwrite entries.
            $this->resultCached = iterator_to_array($this->result, false);
        }

        return $this->resultCached;
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
