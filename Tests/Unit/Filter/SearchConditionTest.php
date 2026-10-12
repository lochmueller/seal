<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Filter;

use CmsIg\Seal\Search\Condition\SearchCondition as SealSearchCondition;
use Lochmueller\Seal\Filter\SearchCondition;
use Lochmueller\Seal\Resolver\SearchRequestDataResolver;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Core\Http\ServerRequest;

class SearchConditionTest extends AbstractTest
{
    private SearchCondition $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new SearchCondition(new SearchRequestDataResolver());
    }

    public function testGetTypeReturnsSearchCondition(): void
    {
        self::assertSame('searchCondition', $this->subject->getType());
    }

    public function testReturnsSearchConditionForSearchTerm(): void
    {
        $request = (new ServerRequest('https://example.com/', 'GET'))
            ->withQueryParams([SearchRequestDataResolver::PARAM_KEY => ['search' => 'TYPO3']]);

        $result = $this->subject->getFilterConfiguration(['type' => 'searchCondition'], $request);

        self::assertCount(1, $result);
        self::assertInstanceOf(SealSearchCondition::class, $result[0]);
        self::assertSame('TYPO3', $result[0]->query);
    }

    public function testReturnsEmptyArrayForEmptySearchTerm(): void
    {
        $request = (new ServerRequest('https://example.com/', 'GET'))
            ->withQueryParams([SearchRequestDataResolver::PARAM_KEY => ['search' => '']]);

        self::assertSame([], $this->subject->getFilterConfiguration(['type' => 'searchCondition'], $request));
    }

    public function testReturnsEmptyArrayForWhitespaceOnlySearchTerm(): void
    {
        $request = (new ServerRequest('https://example.com/', 'GET'))
            ->withQueryParams([SearchRequestDataResolver::PARAM_KEY => ['search' => '   ']]);

        self::assertSame([], $this->subject->getFilterConfiguration(['type' => 'searchCondition'], $request));
    }

    public function testTrimsSearchTerm(): void
    {
        $request = (new ServerRequest('https://example.com/', 'GET'))
            ->withQueryParams([SearchRequestDataResolver::PARAM_KEY => ['search' => '  TYPO3  ']]);

        $result = $this->subject->getFilterConfiguration(['type' => 'searchCondition'], $request);

        self::assertCount(1, $result);
        self::assertInstanceOf(SealSearchCondition::class, $result[0]);
        self::assertSame('TYPO3', $result[0]->query);
    }

    public function testReturnsEmptyArrayForArraySearchTerm(): void
    {
        $request = (new ServerRequest('https://example.com/', 'GET'))
            ->withQueryParams([SearchRequestDataResolver::PARAM_KEY => ['search' => ['TYPO3']]]);

        self::assertSame([], $this->subject->getFilterConfiguration(['type' => 'searchCondition'], $request));
    }

    public function testReturnsEmptyArrayWithoutSearchTerm(): void
    {
        $request = new ServerRequest('https://example.com/', 'GET');

        self::assertSame([], $this->subject->getFilterConfiguration(['type' => 'searchCondition'], $request));
    }

    public function testReturnsEmptyArrayForNonServerRequest(): void
    {
        self::assertSame([], $this->subject->getFilterConfiguration(['type' => 'searchCondition'], $this->createStub(RequestInterface::class)));
    }
}
