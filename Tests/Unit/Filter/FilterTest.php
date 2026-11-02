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

    public function testEnrichFilterRowsDelegatesToTheOwningFilter(): void
    {
        $matching = $this->createStub(FilterInterface::class);
        $matching->method('getType')->willReturn('tagCondition');
        $matching->method('getPartialName')->willReturn('Form/TagCondition');
        $matching->method('enrichFilterRow')->willReturnCallback(
            static function (array $filterRow, array $requestData, array $facetCounts): array {
                $filterRow['enriched'] = [$requestData, $facetCounts];

                return $filterRow;
            },
        );

        $other = $this->createMock(FilterInterface::class);
        $other->method('getType')->willReturn('searchCondition');
        $other->expects(self::never())->method('enrichFilterRow');

        $subject = new Filter([$other, $matching]);

        $result = $subject->enrichFilterRows(
            [['uid' => 5, 'type' => 'tagCondition']],
            ['field_5' => ['file']],
            ['page' => 7],
        );

        self::assertSame([
            [
                'uid' => 5,
                'type' => 'tagCondition',
                'enriched' => [['field_5' => ['file']], ['page' => 7]],
                'partial' => 'Form/TagCondition',
            ],
        ], $result);
    }

    public function testEnrichFilterRowsKeepsRowsOfUnknownTypesUntouched(): void
    {
        $filter = $this->createStub(FilterInterface::class);
        $filter->method('getType')->willReturn('tagCondition');

        $subject = new Filter([$filter]);

        $filterRows = [['uid' => 1, 'type' => 'unknown'], ['uid' => 2]];

        self::assertSame($filterRows, $subject->enrichFilterRows($filterRows));
    }

    public function testEnrichFilterRowsUsesTheFirstRegisteredFilterOfAType(): void
    {
        $first = $this->createStub(FilterInterface::class);
        $first->method('getType')->willReturn('tagCondition');
        $first->method('getPartialName')->willReturn('Form/First');
        $first->method('enrichFilterRow')->willReturnArgument(0);

        $second = $this->createMock(FilterInterface::class);
        $second->method('getType')->willReturn('tagCondition');
        $second->expects(self::never())->method('enrichFilterRow');

        $subject = new Filter([$first, $second]);

        $result = $subject->enrichFilterRows([['uid' => 1, 'type' => 'tagCondition']]);

        self::assertSame('Form/First', $result[0]['partial']);
    }
}
