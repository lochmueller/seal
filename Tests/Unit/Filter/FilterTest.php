<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Filter;

use CmsIg\Seal\Search\Condition\Condition;
use Lochmueller\Seal\Filter\Filter;
use Lochmueller\Seal\Filter\FilterInterface;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use Psr\Http\Message\RequestInterface;

class FilterTest extends AbstractTest
{
    public function testAddsConfigurationOfMatchingFilterType(): void
    {
        $existing = Condition::equal('site', 'main');
        $added = Condition::search('TYPO3');

        $matching = $this->createStub(FilterInterface::class);
        $matching->method('getType')->willReturn('searchCondition');
        $matching->method('getFilterConfiguration')->willReturn([$added]);

        $other = $this->createMock(FilterInterface::class);
        $other->method('getType')->willReturn('tagCondition');
        $other->expects(self::never())->method('getFilterConfiguration');

        $subject = new Filter([$other, $matching]);

        $result = $subject->addFilterConfiguration(
            [$existing],
            ['type' => 'searchCondition'],
            $this->createStub(RequestInterface::class),
        );

        self::assertSame([$existing, $added], $result);
    }

    public function testReturnsInputWhenNoFilterMatches(): void
    {
        $existing = Condition::equal('site', 'main');

        $filter = $this->createStub(FilterInterface::class);
        $filter->method('getType')->willReturn('tagCondition');

        $subject = new Filter([$filter]);

        $result = $subject->addFilterConfiguration(
            [$existing],
            ['type' => 'unknown'],
            $this->createStub(RequestInterface::class),
        );

        self::assertSame([$existing], $result);
    }

    public function testReturnsInputWithoutRegisteredFilters(): void
    {
        $subject = new Filter([]);

        self::assertSame([], $subject->addFilterConfiguration([], ['type' => 'searchCondition'], $this->createStub(RequestInterface::class)));
    }
}
