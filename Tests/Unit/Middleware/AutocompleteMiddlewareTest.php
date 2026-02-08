<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Middleware;

use Lochmueller\Seal\Handler\AutocompleteHandler;
use Lochmueller\Seal\Middleware\AutocompleteMiddleware;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AutocompleteMiddlewareTest extends AbstractTest
{
    private MockObject $autocompleteHandlerMock;

    private AutocompleteMiddleware $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->autocompleteHandlerMock = $this->createMock(AutocompleteHandler::class);
        $this->subject = new AutocompleteMiddleware($this->autocompleteHandlerMock);
    }

    public function testProcessDelegatesToNextHandlerForNonMatchingPath(): void
    {
        $uri = $this->createStub(UriInterface::class);
        $uri->method('getPath')->willReturn('/some/other/path');

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);

        $expectedResponse = $this->createStub(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $this->autocompleteHandlerMock->expects(self::never())->method('handle');

        $result = $this->subject->process($request, $handler);

        self::assertSame($expectedResponse, $result);
    }

    public function testProcessCallsAutocompleteHandlerForMatchingPath(): void
    {
        $uri = $this->createStub(UriInterface::class);
        $uri->method('getPath')->willReturn('/seal/autocomplete');

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);

        $expectedResponse = $this->createStub(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $this->autocompleteHandlerMock->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $result = $this->subject->process($request, $handler);

        self::assertSame($expectedResponse, $result);
    }

    public function testProcessCallsAutocompleteHandlerForPathEndingWithIdentifier(): void
    {
        $uri = $this->createStub(UriInterface::class);
        $uri->method('getPath')->willReturn('/de/seal/autocomplete');

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);

        $expectedResponse = $this->createStub(ResponseInterface::class);

        $handler = $this->createStub(RequestHandlerInterface::class);

        $this->autocompleteHandlerMock->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $result = $this->subject->process($request, $handler);

        self::assertSame($expectedResponse, $result);
    }

    public function testUriPathIdentifierConstant(): void
    {
        $this->autocompleteHandlerMock->expects(self::never())->method('handle');

        self::assertSame('/seal/autocomplete', AutocompleteMiddleware::URI_PATH_IDENTIFIER);
    }
}
