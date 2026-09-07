<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Uri;

use Lochmueller\Seal\Resolver\SearchRequestDataResolver;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use Lochmueller\Seal\Uri\SearchUriBuilder;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class SearchUriBuilderTest extends AbstractTest
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $capturedArguments = null;

    public function testKeepsTheCompleteSearchStateOfTheRequest(): void
    {
        $subject = $this->buildSubject([
            'search' => 'TYPO3',
            'field_12' => ['page', 'file'],
            'geo_position_radius' => '10',
        ]);

        $subject->buildResultPageUri($this->createStub(RequestInterface::class), 3);

        self::assertSame(
            [
                'search' => 'TYPO3',
                'field_12' => ['page', 'file'],
                'geo_position_radius' => '10',
                'currentPageNumber' => 3,
            ],
            $this->capturedArguments,
        );
    }

    public function testOmitsTheDefaultPageSoThatPageOneHasOneCanonicalUri(): void
    {
        $subject = $this->buildSubject(['search' => 'TYPO3', 'currentPageNumber' => '5']);

        $subject->buildResultPageUri($this->createStub(RequestInterface::class));

        self::assertSame(['search' => 'TYPO3'], $this->capturedArguments);
    }

    public function testDropsActionAndControllerBecauseUriForAddsThemItself(): void
    {
        $subject = $this->buildSubject([
            'action' => 'search',
            'controller' => 'Search',
            'search' => 'TYPO3',
        ]);

        $subject->buildResultPageUri($this->createStub(RequestInterface::class));

        self::assertSame(['search' => 'TYPO3'], $this->capturedArguments);
    }

    public function testDropsEmptyFieldsThatTheBrowserSubmittedAnyway(): void
    {
        $subject = $this->buildSubject([
            'search' => 'TYPO3',
            'geo_position_lat' => '',
            'geo_position_lng' => '',
            'field_12' => [],
        ]);

        $subject->buildResultPageUri($this->createStub(RequestInterface::class));

        self::assertSame(['search' => 'TYPO3'], $this->capturedArguments);
    }

    /**
     * Unsetting an entry of a checkbox group must not leave gaps in the keys, otherwise the
     * value would be rendered as tx_seal_search[field_12][1] instead of [].
     */
    public function testReindexesCheckboxGroupsAfterRemovingEmptyValues(): void
    {
        $subject = $this->buildSubject(['field_12' => ['', 'page', '', 'file']]);

        $subject->buildResultPageUri($this->createStub(RequestInterface::class));

        self::assertSame(['field_12' => ['page', 'file']], $this->capturedArguments);
    }

    /**
     * @param array<string, mixed> $requestData
     */
    private function buildSubject(array $requestData): SearchUriBuilder
    {
        $this->capturedArguments = null;

        $resolver = $this->createStub(SearchRequestDataResolver::class);
        $resolver->method('resolve')->willReturn($requestData);

        $uriBuilder = $this->createStub(UriBuilder::class);
        $uriBuilder->method('reset')->willReturnSelf();
        $uriBuilder->method('setRequest')->willReturnSelf();
        $uriBuilder->method('setSection')->willReturnSelf();
        $uriBuilder->method('uriFor')
            ->willReturnCallback(function (?string $action, ?array $arguments, ?string $controller): string {
                $this->capturedArguments = $arguments;
                return '/search';
            });

        return new SearchUriBuilder($uriBuilder, $resolver);
    }
}
