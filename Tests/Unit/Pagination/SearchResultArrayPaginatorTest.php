<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Pagination;

use CmsIg\Seal\Search\Result;
use Lochmueller\Seal\Pagination\SearchResultArrayPaginator;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

class SearchResultArrayPaginatorTest extends AbstractTest
{
    /**
     * Regression: f:for calls count() on the value as soon as "iteration" is used, which fails
     * with "count(): Argument #1 ($value) must be of type Countable|array, Generator given".
     */
    public function testPaginatedItemsAreCountable(): void
    {
        $subject = new SearchResultArrayPaginator($this->buildResult(3), 1, 10);

        $items = $subject->getPaginatedItems();

        self::assertIsArray($items);
        self::assertCount(3, $items);
    }

    public function testPaginatedItemsCanBeIteratedMoreThanOnce(): void
    {
        $subject = new SearchResultArrayPaginator($this->buildResult(2), 1, 10);

        $first = iterator_to_array((function () use ($subject): \Generator {
            yield from $subject->getPaginatedItems();
        })());
        $second = iterator_to_array((function () use ($subject): \Generator {
            yield from $subject->getPaginatedItems();
        })());

        self::assertSame([['id' => '0'], ['id' => '1']], $first);
        self::assertSame($first, $second);
    }

    public function testPaginatedItemsAreAListEvenIfTheAdapterYieldsItsOwnKeys(): void
    {
        $documents = (static function (): \Generator {
            yield 7 => ['id' => 'a'];
            yield 7 => ['id' => 'b'];
        })();

        $subject = new SearchResultArrayPaginator(new Result($documents, 2), 1, 10);

        self::assertSame([['id' => 'a'], ['id' => 'b']], $subject->getPaginatedItems());
    }

    private function buildResult(int $amount): Result
    {
        $documents = (static function () use ($amount): \Generator {
            for ($i = 0; $i < $amount; ++$i) {
                yield ['id' => (string) $i];
            }
        })();

        return new Result($documents, $amount);
    }
}
