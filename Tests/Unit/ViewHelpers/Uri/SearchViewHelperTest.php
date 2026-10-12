<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\ViewHelpers\Uri;

use Lochmueller\Seal\Tests\Unit\AbstractTest;
use Lochmueller\Seal\Uri\SearchUriBuilder;
use Lochmueller\Seal\ViewHelpers\Uri\SearchViewHelper;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class SearchViewHelperTest extends AbstractTest
{
    public function testRenderBuildsResultPageUri(): void
    {
        $request = $this->createStub(RequestInterface::class);

        $searchUriBuilder = $this->createMock(SearchUriBuilder::class);
        $searchUriBuilder->expects(self::once())
            ->method('buildResultPageUri')
            ->with($request, 2, 'seal-12-results')
            ->willReturn('/search?page=2#seal-12-results');

        $subject = new SearchViewHelper($searchUriBuilder);
        $subject->setRenderingContext($this->createRenderingContext($request));
        $subject->setArguments(['page' => 2, 'section' => 'seal-12-results']);

        self::assertSame('/search?page=2#seal-12-results', $subject->render());
    }

    public function testRenderReturnsEmptyStringWithoutRequest(): void
    {
        $searchUriBuilder = $this->createMock(SearchUriBuilder::class);
        $searchUriBuilder->expects(self::never())->method('buildResultPageUri');

        $subject = new SearchViewHelper($searchUriBuilder);
        $subject->setRenderingContext($this->createRenderingContext(null));
        $subject->setArguments(['page' => 1, 'section' => '']);

        self::assertSame('', $subject->render());
    }

    public function testRenderReturnsEmptyStringForNonExtbaseRequest(): void
    {
        $searchUriBuilder = $this->createMock(SearchUriBuilder::class);
        $searchUriBuilder->expects(self::never())->method('buildResultPageUri');

        $subject = new SearchViewHelper($searchUriBuilder);
        $subject->setRenderingContext($this->createRenderingContext($this->createStub(ServerRequestInterface::class)));
        $subject->setArguments(['page' => 1, 'section' => '']);

        self::assertSame('', $subject->render());
    }

    public function testRegistersArguments(): void
    {
        $subject = new SearchViewHelper($this->createStub(SearchUriBuilder::class));
        $arguments = $subject->prepareArguments();

        self::assertSame(1, $arguments['page']->getDefaultValue());
        self::assertSame('', $arguments['section']->getDefaultValue());
    }

    private function createRenderingContext(?object $request): RenderingContextInterface
    {
        $renderingContext = $this->createStub(RenderingContextInterface::class);
        $renderingContext->method('hasAttribute')->willReturn($request !== null);
        if ($request !== null) {
            $renderingContext->method('getAttribute')->willReturn($request);
        }
        return $renderingContext;
    }
}
