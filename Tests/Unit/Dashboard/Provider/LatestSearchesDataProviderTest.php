<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Dashboard\Provider;

use Lochmueller\Seal\Dashboard\Provider\LatestSearchesDataProvider;
use Lochmueller\Seal\Repository\StatRepository;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

if (!interface_exists(\TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface::class)) {
    require_once __DIR__ . '/Fixtures/ListDataProviderInterface.php';
}

class LatestSearchesDataProviderTest extends AbstractTest
{
    public function testGetItemsTransformsRepositoryDataCorrectly(): void
    {
        $repositoryData = [
            ['search_term' => 'TYPO3', 'site' => 'main', 'language' => '0', 'crdate' => 1717200000],
            ['search_term' => 'SEAL', 'site' => 'portal', 'language' => '1', 'crdate' => 1717113600],
        ];

        $statRepository = $this->createStub(StatRepository::class);
        $statRepository->method('findLatest')->willReturn($repositoryData);

        $subject = new LatestSearchesDataProvider($statRepository);
        $items = $subject->getItems();

        self::assertCount(2, $items);
        self::assertSame('TYPO3 / main / 0 / ' . date('Y-m-d H:i', 1717200000), $items[0]);
        self::assertSame('SEAL / portal / 1 / ' . date('Y-m-d H:i', 1717113600), $items[1]);
    }

    public function testGetItemsReturnsEmptyArrayWhenNoData(): void
    {
        $statRepository = $this->createStub(StatRepository::class);
        $statRepository->method('findLatest')->willReturn([]);

        $subject = new LatestSearchesDataProvider($statRepository);

        self::assertSame([], $subject->getItems());
    }
}
