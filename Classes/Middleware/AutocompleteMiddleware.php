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
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

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

        /** @var SiteInterface $site */
        $site = $request->getAttributes()['site'];

        $configuration = $site->getConfiguration();
        $minChars = $configuration['sealAutocompleteMinCharacters'] ?? 3;

        if (strlen($searchWord) < (int) $minChars) {
            return new JsonResponse([], 204, ['X-Seal-Info' => 'To less chars for auto complete functions']);
        }

        /** @var SiteLanguage $language */
        $language = $request->getAttributes()['language'] ?? null;

        $engine = $this->seal->buildEngineBySite($site);


        $filter = [];
        $filter[] = new SearchCondition($searchWord);
        // @todo search in the right way
        #$filter[] = new EqualCondition('site', $site->getIdentifier());
        #$filter[] = new EqualCondition('language', $language->getLanguageId());

        $result = $engine->createSearchBuilder(SchemaBuilder::DEFAULT_INDEX)
            ->addFilter(new AndCondition(...$filter))
            ->limit(25)
            ->getResult();

        $data = [];
        foreach ($result as $item) {
            $data = array_merge($data, $this->findSuggestions($searchWord, $item['title'] . ' ' . $item['content']));
        }

        return new JsonResponse(array_unique($data), 200);
    }

    public function findSuggestions($searchWord, $content)
    {
        $suggestions = [];

        preg_match_all('/\b\w+\b/u', $content, $matches);

        foreach ($matches[0] as $word) {
            if (mb_strpos(strtolower($word), strtolower($searchWord)) === 0) {
                $suggestions[$word] = true;
            }
        }

        return array_keys($suggestions); // eindeutige Vorschläge
    }


}
