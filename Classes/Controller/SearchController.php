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
use Lochmueller\Seal\Schema\SchemaBuilder;
use Lochmueller\Seal\Seal;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Pagination\PaginationInterface;
use TYPO3\CMS\Core\Pagination\PaginatorInterface;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SearchController extends AbstractSealController
{
    public function __construct(
        private readonly Seal                  $seal,
        protected ConfigurationLoader $configurationLoader,
        protected Filter              $filter,
        private readonly TagConfigurationParser $tagConfigurationParser,
        private readonly RadiusConfigurationParser $radiusConfigurationParser,
    ) {}

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

        $engine = $this->seal->buildEngineBySite($site);

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

        $searchBuilder = $engine->createSearchBuilder(SchemaBuilder::DEFAULT_INDEX);
        foreach ($filter as $condition) {
            $searchBuilder->addFilter($condition);
        }

        if ($hasTagCondition) {
            $searchBuilder->addFacet(Facet::count('tags'));
        }

        $result = $searchBuilder
            ->limit($config->itemsPerPage)
            ->offset(($currentPage - 1) * $config->itemsPerPage)
            ->highlight(['title'])
            ->getResult();

        $facets = $result->facets();
        $tagFacets = $facets['tags'] ?? [];
        $tagFacetCounts = $tagFacets['count'] ?? [];

        $parsedBody = $this->request->getParsedBody();
        $requestData = is_array($parsedBody) ? ($parsedBody['tx_seal_search'] ?? []) : [];

        foreach ($filterRows as &$filterRow) {
            if ($filterRow['type'] !== 'tagCondition') {
                continue;
            }
            $configuredTags = $this->tagConfigurationParser->parse((string) ($filterRow['tags'] ?? ''));
            $selectedValues = (array) ($requestData['field_' . $filterRow['uid']] ?? []);

            $filterRow['parsedTags'] = array_map(
                static fn(array $tag): array => [
                    'value' => $tag['value'],
                    'label' => $tag['label'],
                    'count' => $tagFacetCounts[$tag['value']] ?? 0,
                    'selected' => in_array($tag['value'], $selectedValues, true),
                ],
                $configuredTags,
            );
        }
        unset($filterRow);

        foreach ($filterRows as &$filterRow) {
            if ($filterRow['type'] !== 'geoDistanceCondition') {
                continue;
            }
            $configuredRadii = $this->radiusConfigurationParser->parse(
                (string) ($filterRow['radius_steps'] ?? '')
            );
            $filterRow['parsedRadii'] = $configuredRadii;
        }
        unset($filterRow);

        $paginator = new SearchResultArrayPaginator($result, $currentPage, $config->itemsPerPage);

        $this->view->assignMultiple(
            [
                'filters' => $filterRows,
                'tagFacets' => $tagFacets,
                'pagination' => $this->getPagination(SimplePagination::class, 6, $paginator),
                'paginator' => $paginator,
                'currentPageNumber' => $currentPage,
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
