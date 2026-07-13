<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Resolver;

use Psr\Http\Message\ServerRequestInterface;

class SearchRequestDataResolver
{
    private const string PARAM_KEY = 'tx_seal_search';

    public function resolve(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $data = (\is_array($body) && isset($body[self::PARAM_KEY])) ? $body[self::PARAM_KEY] : ($queryParams[self::PARAM_KEY] ?? []);
        return \is_array($data) ? $data : [];
    }

}
