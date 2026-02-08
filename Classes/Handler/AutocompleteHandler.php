<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Handler;

use CmsIg\Seal\Search\Condition\Condition;
use Lochmueller\Seal\Configuration\ConfigurationLoader;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Lochmueller\Seal\Seal;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class AutocompleteHandler implements RequestHandlerInterface
{
    public function __construct(protected Seal $seal, protected ConfigurationLoader $configurationLoader) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $searchWord = trim($params['q'] ?? '');

        /** @var SiteInterface $site */
        $site = $request->getAttributes()['site'];

        $config = $this->configurationLoader->loadBySite($site);

        if (strlen($searchWord) < $config->autocompleteMinCharacters) {
            return new JsonResponse([], 204, ['X-Seal-Info' => 'To less chars for auto complete functions']);
        }

        $filter = [
            Condition::search($searchWord),
            Condition::equal('site', $site->getIdentifier()),
            Condition::equal('language', (string) (($request->getAttributes()['language'] ?? null)?->getLanguageId() ?? 0)),
        ];

        $searchBuilder = $this->seal->buildEngineBySite($site)->createSearchBuilder(SchemaBuilder::DEFAULT_INDEX);
        foreach ($filter as $condition) {
            $searchBuilder->addFilter($condition);
        }
        $result = $searchBuilder
            ->limit(25)
            ->getResult();

        $data = [];
        foreach ($result as $item) {
            $data = array_merge($data, $this->findSuggestions($searchWord, $item['title'] . ' ' . $item['content']));
        }

        return new JsonResponse(array_unique($data), 200, ['X-Seal-Info' => $result->total() . ' search items']);
    }

    /**
     * @return array<int, string>
     */
    public function findSuggestions(string $searchWord, string $content): array
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
