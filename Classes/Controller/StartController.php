<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Controller;

use CmsIg\Seal\Search\Condition\Condition;
use CmsIg\Seal\Search\Facet\Facet;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class StartController extends AbstractSealController
{
    public function startAction(): ResponseInterface
    {
        $currentContentElementData = $this->getCurrentContentElementRow();

        if (
            isset($currentContentElementData['seal_show_initial_results']) &&
            (bool)$currentContentElementData['seal_show_initial_results'] === true) {
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

        $this->view->assignMultiple([
            'filters' => $this->addCalculatedValuesForFilterRows(
                $filterRows,
                tagFacetCounts: $tagFacetCounts
            ),
        ]);

        return $this->htmlResponse();
    }
}
