<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Controller;

use CmsIg\Seal\Search\Condition\Condition;
use CmsIg\Seal\Search\Facet\Facet;
use Lochmueller\Seal\Filter\RadiusConfigurationParser;
use Lochmueller\Seal\Filter\TagConfigurationParser;
use Lochmueller\Seal\Resolver\SearchRequestDataResolver;
use Lochmueller\Seal\Seal;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class StartController extends AbstractSealController
{
    public function __construct(
        TagConfigurationParser $tagConfigurationParser,
        RadiusConfigurationParser $radiusConfigurationParser,
        Seal $seal,
        protected SearchRequestDataResolver $searchRequestDataResolver,
    ) {
        parent::__construct($tagConfigurationParser, $radiusConfigurationParser, $seal);
    }

    public function startAction(): ResponseInterface
    {
        $currentContentElementData = $this->getCurrentContentElementRow();

        if (
            isset($currentContentElementData['seal_show_initial_results'])
            && (bool) $currentContentElementData['seal_show_initial_results'] === true) {
            return $this->redirect('search', 'Search');
        }

        $filterRows = iterator_to_array($this->getFilterRowsByContentElementUid($currentContentElementData['uid']));

        $hasTagCondition = false;
        foreach ($filterRows as $filterItem) {
            if ($filterItem['type'] === 'tagCondition') {
                $hasTagCondition = true;
            }
        }        /** @var Site $site */
        $site = $this->request->getAttribute('site');
        /** @var SiteLanguage $language */
        $language = $this->request->getAttribute('language');


        $filter = [];
        $filter[] = Condition::equal('site', $site->getIdentifier());
        $filter[] = Condition::equal('language', (string) $language->getLanguageId());
        $searchBuilder = $this->getSearchBuilder();
        foreach ($filter as $condition) {
            $searchBuilder->addFilter($condition);
        }

        if ($hasTagCondition) {
            $searchBuilder->addFacet(Facet::count('tags'));
        }
        $result = $searchBuilder->getResult();


        $facets = $result->facets();
        $tagFacets = $facets['tags'] ?? [];
        $tagFacetCounts = $tagFacets['count'] ?? [];

        // The form is rendered with the same partials as on the result page, so the current
        // search state has to be available here as well to pre-fill the fields.
        $requestData = $this->searchRequestDataResolver->resolve($this->request);

        $this->view->assignMultiple([
            'sealId' => $this->buildSealId((int) $currentContentElementData['uid']),
            'pluginNamespace' => SearchRequestDataResolver::PARAM_KEY,
            'requestData' => $requestData,
            'filters' => $this->addCalculatedValuesForFilterRows(
                $filterRows,
                $requestData,
                $tagFacetCounts
            ),
        ]);

        return $this->htmlResponse();
    }
}
