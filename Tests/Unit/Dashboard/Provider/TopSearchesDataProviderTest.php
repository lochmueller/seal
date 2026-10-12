<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Dashboard\Provider;

use Lochmueller\Seal\Dashboard\Provider\TopSearchesDataProvider;
use Lochmueller\Seal\Repository\StatRepository;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

if (!interface_exists(\TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface::class)) {
    require_once __DIR__ . '/Fixtures/ListDataProviderInterface.php';
}

class TopSearchesDataProviderTest extends AbstractTest
{
    public function testGetItemsTransformsRepositoryDataCorrectly(): void
    {
        $repositoryData = [
            ['search_term' => 'popular', 'site' => 'main', 'count' => 42],
            ['search_term' => 'popular', 'site' => 'shop', 'count' => 12],
            ['search_term' => 'less popular', 'site' => 'main', 'count' => 10],
        ];

        $statRepository = $this->createStub(StatRepository::class);
        $statRepository->method('findTopSearchesOfCurrentMonth')->willReturn($repositoryData);

        $subject = new TopSearchesDataProvider($statRepository);
        $items = $subject->getItems();

        self::assertCount(3, $items);
        self::assertSame('popular / main / 42', $items[0]);
        self::assertSame('popular / shop / 12', $items[1]);
        self::assertSame('less popular / main / 10', $items[2]);
    }

    public function testGetItemsReturnsEmptyArrayWhenNoData(): void
    {
        $statRepository = $this->createStub(StatRepository::class);
        $statRepository->method('findTopSearchesOfCurrentMonth')->willReturn([]);

        $subject = new TopSearchesDataProvider($statRepository);

        self::assertSame([], $subject->getItems());
    }
}
