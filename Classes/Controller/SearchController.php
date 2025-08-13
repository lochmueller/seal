<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Controller;

use CmsIg\Seal\Search\Condition\AndCondition;
use CmsIg\Seal\Search\Condition\SearchCondition;
use Lochmueller\Seal\Pagination\SearchResultArrayPaginator;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Lochmueller\Seal\Seal;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class SearchController extends ActionController
{
    public function __construct(
        private Seal $seal,
    ) {}

    public function listAction(): ResponseInterface
    {
        $engine = $this->seal->buildEngineBySite($GLOBALS['TYPO3_REQUEST']->getAttribute('site'));

        $currentPage = $this->request->hasArgument('currentPageNumber')
            ? (int) $this->request->getArgument('currentPageNumber')
            : 1;

        $search = $this->request->getParsedBody()['tx_seal_search']['search'] ?? '';
        $pageSize = 5;

        $filter = [];
        $filter[] = new SearchCondition($search);

        // @todo Add more here
        // GEO
        // Tags

        $result = $engine->createSearchBuilder(SchemaBuilder::DEFAULT_INDEX)
            ->addFilter(new AndCondition(...$filter))
            ->limit($pageSize)
            ->offset(($currentPage - 1) * $pageSize)
            ->highlight(['title'])
            ->getResult();


        $paginator = new SearchResultArrayPaginator($result, $currentPage, $pageSize);

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
