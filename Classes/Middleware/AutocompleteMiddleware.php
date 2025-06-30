<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Middleware;

use CmsIg\Seal\Search\Condition\AndCondition;
use CmsIg\Seal\Search\Condition\EqualCondition;
use CmsIg\Seal\Search\Condition\SearchCondition;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Lochmueller\Seal\Seal;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class AutocompleteMiddleware implements MiddlewareInterface
{
    public function __construct(protected Seal $seal) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        if (!str_ends_with($request->getUri()->getPath(), '/seal/autocomplete')) {
            return $handler->handle($request);
        }

        $params = $request->getQueryParams();
        $searchWord = trim($params['q'] ?? '');


        if (strlen($searchWord) < 2) {
            // @todo return empty search result
            // return $handler->handle($request);
        }


        /** @var SiteLanguage $language */
        $language = $request->getAttributes()['language'] ?? null;
        /** @var SiteInterface $site */
        $site = $request->getAttributes()['site'];

        $engine = $this->seal->buildEngineBySite($site);


        $filter = [];
        $filter[] = new SearchCondition($searchWord);
        $filter[] = new EqualCondition('site', $site->getIdentifier());
        $filter[] = new EqualCondition('language', $language->getLanguageId());


        $result = $engine->createSearchBuilder(SchemaBuilder::DEFAULT_INDEX)
            ->addFilter(new AndCondition(...$filter))
            ->limit(25)
            ->getResult();

        // Extract from result
        foreach ($result as $item) {
            #DebuggerUtility::var_dump($item);
            // @todo return search results
        }

        return $handler->handle($request);
    }
}
