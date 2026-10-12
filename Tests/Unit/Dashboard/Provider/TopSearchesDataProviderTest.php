<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Dashboard\Provider;

use Lochmueller\Seal\Dashboard\Provider\TopSearchesDataProvider;
use Lochmueller\Seal\Repository\StatRepository;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

class TopSearchesDataProviderTest extends AbstractTest
{
    public function testGetRowsTransformsRepositoryDataCorrectly(): void
    {
        $repositoryData = [
            ['search_term' => 'popular', 'site' => 'main', 'count' => 42],
            ['search_term' => 'popular', 'site' => 'shop', 'count' => 12],
            ['search_term' => 'less popular', 'site' => 'main', 'count' => 10],
        ];

        $statRepository = $this->createStub(StatRepository::class);
        $statRepository->method('findTopSearchesOfCurrentMonth')->willReturn($repositoryData);

        $subject = new TopSearchesDataProvider($statRepository);
        $rows = $subject->getRows();

        self::assertCount(3, $rows);
        self::assertSame(['popular', 'main', '42'], $rows[0]);
        self::assertSame(['popular', 'shop', '12'], $rows[1]);
        self::assertSame(['less popular', 'main', '10'], $rows[2]);
    }

    public function testGetRowsReturnsEmptyArrayWhenNoData(): void
    {
        $statRepository = $this->createStub(StatRepository::class);
        $statRepository->method('findTopSearchesOfCurrentMonth')->willReturn([]);

        $subject = new TopSearchesDataProvider($statRepository);

        self::assertSame([], $subject->getRows());
    }
}
