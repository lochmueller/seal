<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Uri;

use Lochmueller\Seal\Resolver\SearchRequestDataResolver;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/**
 * Builds URIs that carry the complete current search state.
 *
 * The search form is submitted via GET, so every link that should keep the current search
 * (pagination today, facet links later) has to re-create the search arguments explicitly.
 * "addQueryString" is not an option here: with a trusted value it only keeps the route
 * arguments of the request (PageLinkBuilder::getQueryArguments()) and would silently drop
 * the search, and "untrusted" would forward every unrelated query parameter as well.
 */
class SearchUriBuilder
{
    public function __construct(
        private readonly UriBuilder $uriBuilder,
        private readonly SearchRequestDataResolver $searchRequestDataResolver,
    ) {}

    /**
     * @param int $page Result page the URI should point to. Page 1 is the default and is
     *                  omitted, so that the first page always has one canonical URI.
     */
    public function buildResultPageUri(RequestInterface $request, int $page = 1, string $section = ''): string
    {
        $arguments = $this->searchRequestDataResolver->resolve($request);

        // "action" and "controller" are added by uriFor(), the page is set explicitly.
        unset($arguments['action'], $arguments['controller'], $arguments['currentPageNumber']);

        if ($page > 1) {
            $arguments['currentPageNumber'] = $page;
        }

        return $this->uriBuilder
            ->reset()
            ->setRequest($request)
            ->setSection($section)
            ->uriFor('search', $this->removeEmptyValues($arguments), 'Search', 'Seal', 'Search');
    }

    /**
     * Empty form fields are submitted as empty strings by the browser. Keeping them would
     * bloat every result URI and would produce a different URI for the same search.
     *
     * @param array<array-key, mixed> $arguments
     * @return array<array-key, mixed>
     */
    private function removeEmptyValues(array $arguments): array
    {
        // Checkbox groups arrive as lists; unsetting an entry would leave gaps in the keys.
        $isList = array_is_list($arguments);

        foreach ($arguments as $key => $value) {
            if (\is_array($value)) {
                $value = $this->removeEmptyValues($value);
                $arguments[$key] = $value;
            }

            if ($value === '' || $value === null || $value === []) {
                unset($arguments[$key]);
            }
        }

        return $isList ? array_values($arguments) : $arguments;
    }
}
