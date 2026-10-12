<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Resolver;

use Lochmueller\Seal\Resolver\SearchRequestDataResolver;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use TYPO3\CMS\Core\Http\ServerRequest;

class SearchRequestDataResolverTest extends AbstractTest
{
    public function testResolvesDataFromParsedBody(): void
    {
        $request = (new ServerRequest('https://example.com/', 'POST'))
            ->withParsedBody([SearchRequestDataResolver::PARAM_KEY => ['search' => 'body']]);

        self::assertSame(['search' => 'body'], (new SearchRequestDataResolver())->resolve($request));
    }

    public function testResolvesDataFromQueryParams(): void
    {
        $request = (new ServerRequest('https://example.com/', 'GET'))
            ->withQueryParams([SearchRequestDataResolver::PARAM_KEY => ['search' => 'query']]);

        self::assertSame(['search' => 'query'], (new SearchRequestDataResolver())->resolve($request));
    }

    public function testParsedBodyTakesPrecedenceOverQueryParams(): void
    {
        $request = (new ServerRequest('https://example.com/', 'POST'))
            ->withQueryParams([SearchRequestDataResolver::PARAM_KEY => ['search' => 'query']])
            ->withParsedBody([SearchRequestDataResolver::PARAM_KEY => ['search' => 'body']]);

        self::assertSame(['search' => 'body'], (new SearchRequestDataResolver())->resolve($request));
    }

    public function testFallsBackToQueryParamsWhenBodyHasNoPluginData(): void
    {
        $request = (new ServerRequest('https://example.com/', 'POST'))
            ->withQueryParams([SearchRequestDataResolver::PARAM_KEY => ['search' => 'query']])
            ->withParsedBody(['other' => 'value']);

        self::assertSame(['search' => 'query'], (new SearchRequestDataResolver())->resolve($request));
    }

    public function testReturnsEmptyArrayWithoutPluginData(): void
    {
        $request = new ServerRequest('https://example.com/', 'GET');

        self::assertSame([], (new SearchRequestDataResolver())->resolve($request));
    }

    public function testReturnsEmptyArrayForNonArrayPluginData(): void
    {
        $request = (new ServerRequest('https://example.com/', 'GET'))
            ->withQueryParams([SearchRequestDataResolver::PARAM_KEY => 'invalid']);

        self::assertSame([], (new SearchRequestDataResolver())->resolve($request));
    }
}
