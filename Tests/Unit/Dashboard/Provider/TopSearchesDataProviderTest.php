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
            ['search_term' => 'popular', 'count' => 42],
            ['search_term' => 'less popular', 'count' => 10],
        ];

        $statRepository = $this->createStub(StatRepository::class);
        $statRepository->method('findTopSearchesOfCurrentMonth')->willReturn($repositoryData);

        $subject = new TopSearchesDataProvider($statRepository);
        $items = $subject->getItems();

        self::assertCount(2, $items);
        self::assertSame('popular / 42', $items[0]);
        self::assertSame('less popular / 10', $items[1]);
    }

    public function testGetItemsReturnsEmptyArrayWhenNoData(): void
    {
        $statRepository = $this->createStub(StatRepository::class);
        $statRepository->method('findTopSearchesOfCurrentMonth')->willReturn([]);

        $subject = new TopSearchesDataProvider($statRepository);

        self::assertSame([], $subject->getItems());
    }
}
