<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Middleware;

use Lochmueller\Seal\Handler\AutocompleteHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AutocompleteMiddleware implements MiddlewareInterface
{
    public const URI_PATH_IDENTIFIER = '/seal/autocomplete';

    public function __construct(protected AutocompleteHandler $autocompleteHandler) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!str_ends_with($request->getUri()->getPath(), self::URI_PATH_IDENTIFIER)) {
            return $handler->handle($request);
        }

        $this->autocompleteHandler->handle($request);
    }

}
