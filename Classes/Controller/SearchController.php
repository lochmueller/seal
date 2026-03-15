<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Controller;

use CmsIg\Seal\Search\Condition\Condition;
use CmsIg\Seal\Search\Facet\Facet;
use GeorgRinger\NumberedPagination\NumberedPagination;
use Lochmueller\Seal\Configuration\ConfigurationLoader;
use Lochmueller\Seal\Filter\Filter;
use Lochmueller\Seal\Filter\RadiusConfigurationParser;
use Lochmueller\Seal\Filter\TagConfigurationParser;
use Lochmueller\Seal\Pagination\SearchResultArrayPaginator;
use Lochmueller\Seal\Repository\StatRepository;
use Lochmueller\Seal\Seal;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Pagination\PaginationInterface;
use TYPO3\CMS\Core\Pagination\PaginatorInterface;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SearchController extends AbstractSealController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        TagConfigurationParser $tagConfigurationParser,
        RadiusConfigurationParser $radiusConfigurationParser,
        Seal $seal,
        protected ConfigurationLoader $configurationLoader,
        protected Filter $filter,
        private readonly StatRepository $statRepository,
    ) {
        parent::__construct($tagConfigurationParser, $radiusConfigurationParser, $seal);
    }

    public function searchAction(): ResponseInterface
    {
        $currentPage = $this->request->hasArgument('currentPageNumber')
            ? (int) $this->request->getArgument('currentPageNumber')
            : 1;

        /** @var Site $site */
        $site = $this->request->getAttribute('site');
        /** @var SiteLanguage $language */
        $language = $this->request->getAttribute('language');

        $config = $this->configurationLoader->loadBySite($site);

        $filterRows = iterator_to_array($this->getFilterRowsByContentElementUid($this->getCurrentContentElementRow()['uid']));

        $filter = [];
        $hasTagCondition = false;
        foreach ($filterRows as $filterItem) {
            $filter = $this->filter->addFilterConfiguration($filter, $filterItem, $this->request);
            if ($filterItem['type'] === 'tagCondition') {
                $hasTagCondition = true;
            }
        }

        $filter[] = Condition::equal('site', $site->getIdentifier());
        $filter[] = Condition::equal('language', (string) $language->getLanguageId());

        $searchBuilder = $this->getSearchBuilder();
        foreach ($filter as $condition) {
            $searchBuilder->addFilter($condition);
        }

        if ($hasTagCondition) {
            $searchBuilder->addFacet(Facet::count('tags'));
        }

        $result = $searchBuilder
            ->limit($config->itemsPerPage)
            ->offset(($currentPage - 1) * $config->itemsPerPage)
            ->getResult();

        $parsedBody = $this->request->getParsedBody();
        $searchTerm = is_array($parsedBody) ? (string) ($parsedBody['tx_seal_search']['search'] ?? '') : '';

        try {
            $this->statRepository->logSearchQuery($searchTerm, $site->getIdentifier(), $language->getLanguageId());
        } catch (\Exception $exception) {
            $this->logger?->error($exception->getMessage(), ['exception' => $exception]);
        }

        $facets = $result->facets();
        $tagFacets = $facets['tags'] ?? [];
        $tagFacetCounts = $tagFacets['count'] ?? [];

        $requestData = is_array($parsedBody) ? ($parsedBody['tx_seal_search'] ?? []) : [];

        $paginator = new SearchResultArrayPaginator($result, $currentPage, $config->itemsPerPage);

        $this->view->assignMultiple(
            [
                'filters' => $this->addCalculatedValuesForFilterRows($filterRows, $requestData, $tagFacetCounts),
                'tagFacets' => $tagFacets,
                'pagination' => $this->getPagination(SimplePagination::class, 6, $paginator),
                'paginator' => $paginator,
                'currentPageNumber' => $currentPage,
                'requestData' => $requestData,
            ],
        );

        return $this->htmlResponse();
    }

    protected function getPagination(string $paginationClass, int $maximumNumberOfLinks, PaginatorInterface $paginator): PaginationInterface
    {
        if (class_exists(NumberedPagination::class) && $paginationClass === NumberedPagination::class && $maximumNumberOfLinks) {
            /** @var PaginationInterface */
            return GeneralUtility::makeInstance(NumberedPagination::class, $paginator, $maximumNumberOfLinks);
        } elseif (class_exists(SlidingWindowPagination::class) && $paginationClass === SlidingWindowPagination::class && $maximumNumberOfLinks) {
            /** @var PaginationInterface */
            return GeneralUtility::makeInstance(SlidingWindowPagination::class, $paginator, $maximumNumberOfLinks);
        } elseif (class_exists($paginationClass)) {
            /** @var PaginationInterface */
            return GeneralUtility::makeInstance($paginationClass, $paginator);
        }
        return GeneralUtility::makeInstance(SimplePagination::class, $paginator);
    }

}
