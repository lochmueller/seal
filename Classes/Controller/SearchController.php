<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Controller;

use CmsIg\Seal\Search\Condition\SearchCondition;
use Lochmueller\Seal\Pagination\SearchResultArrayPaginator;
use Lochmueller\Seal\Seal;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class SearchController extends ActionController
{
    public function __construct(
        private Seal $seal,
    ) {}

    public function listAction(): ResponseInterface
    {
        $engine = $this->seal->buildEngine();

        $result = $engine->createSearchBuilder('page')
            ->addFilter(new SearchCondition('Test'))
            ->getResult();


        $currentPage = $this->request->hasArgument('currentPageNumber')
            ? (int) $this->request->getArgument('currentPageNumber')
            : 1;

        $itemsPerPage = 2;

        $paginator = new SearchResultArrayPaginator($result, $currentPage, $itemsPerPage);

        $this->view->assignMultiple(
            [
                'pagination' => new SimplePagination($paginator),
                'paginator' => $paginator,
            ],
        );


        return $this->htmlResponse();
    }

    public function searchAction(): ResponseInterface
    {
        $this->view->assignMultiple([
            // @todo
        ]);

        return $this->htmlResponse();
    }

}
