<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Controller;

use Lochmueller\Seal\Configuration\Configuration;
use Lochmueller\Seal\Configuration\ConfigurationLoader;
use Lochmueller\Seal\Pagination\SearchResultArrayPaginator;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Lochmueller\Seal\Seal;
use Psr\Http\Message\ResponseInterface;
use CmsIg\Seal\Search\Condition\Condition;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use GeorgRinger\NumberedPagination\NumberedPagination;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class SearchController extends ActionController
{
    public function __construct(
        private Seal                  $seal,
        protected ConfigurationLoader $configurationLoader,
    ) {}

    public function searchAction(): ResponseInterface
    {
        $engine = $this->seal->buildEngineBySite($GLOBALS['TYPO3_REQUEST']->getAttribute('site'));

        $currentPage = $this->request->hasArgument('currentPageNumber')
            ? (int) $this->request->getArgument('currentPageNumber')
            : 1;

        $search = $this->request->getParsedBody()['tx_seal_search']['search'] ?? '';

        /** @var Site $site */
        $site = $this->request->getAttribute('site');
        /** @var SiteLanguage $language */
        $language = $this->request->getAttribute('language');

        $config = $this->configurationLoader->loadBySite($site);

        $config = new Configuration(searchDsn:'typo3://',itemsPerPage: 1, autocompleteMinCharacters: 1);

        $filter = [];
        $filter[] = Condition::search($search);
        $filter[] = Condition::equal('site', $site->getIdentifier());
        $filter[] = Condition::equal('language', (string) $language->getLanguageId());

        // @todo Add more here
        // GEO
        // Tags

        $result = $engine->createSearchBuilder(SchemaBuilder::DEFAULT_INDEX)
            ->addFilter(Condition::and(...$filter))
            ->limit($config->itemsPerPage)
            ->offset(($currentPage - 1) * $config->itemsPerPage)
            ->highlight(['title'])
            ->getResult();


        $paginator = new SearchResultArrayPaginator($result, $currentPage, $config->itemsPerPage);

        $this->view->assignMultiple(
            [
                // @todo configure Pagination class
                'pagination' => $this->getPagination(SimplePagination::class, 6, $paginator),
                'paginator' => $paginator,
                'currentPageNumber' => $currentPage,
            ],
        );

        return $this->htmlResponse();
    }

    protected function getPagination($paginationClass, int $maximumNumberOfLinks, $paginator)
    {
        if (class_exists(NumberedPagination::class) && $paginationClass === NumberedPagination::class && $maximumNumberOfLinks) {
            return GeneralUtility::makeInstance(NumberedPagination::class, $paginator, $maximumNumberOfLinks);
        } elseif (class_exists(SlidingWindowPagination::class) && $paginationClass === SlidingWindowPagination::class && $maximumNumberOfLinks) {
            return GeneralUtility::makeInstance(SlidingWindowPagination::class, $paginator, $maximumNumberOfLinks);
        } elseif (class_exists($paginationClass)) {
            return GeneralUtility::makeInstance($paginationClass, $paginator);
        }
        return GeneralUtility::makeInstance(SimplePagination::class, $paginator);
    }

}
