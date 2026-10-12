<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Dashboard\Provider;

use Lochmueller\Seal\Dashboard\Provider\LatestSearchesDataProvider;
use Lochmueller\Seal\Repository\StatRepository;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

class LatestSearchesDataProviderTest extends AbstractTest
{
    public function testGetRowsTransformsRepositoryDataCorrectly(): void
    {
        $repositoryData = [
            ['search_term' => 'TYPO3', 'site' => 'main', 'language' => '0', 'crdate' => 1717200000],
            ['search_term' => 'SEAL', 'site' => 'portal', 'language' => '1', 'crdate' => 1717113600],
        ];

        $statRepository = $this->createStub(StatRepository::class);
        $statRepository->method('findLatest')->willReturn($repositoryData);

        $subject = new LatestSearchesDataProvider($statRepository);
        $rows = $subject->getRows();

        self::assertCount(2, $rows);
        self::assertSame(['TYPO3', 'main', '0', date('Y-m-d H:i', 1717200000)], $rows[0]);
        self::assertSame(['SEAL', 'portal', '1', date('Y-m-d H:i', 1717113600)], $rows[1]);
    }

    public function testGetRowsReturnsEmptyArrayWhenNoData(): void
    {
        $statRepository = $this->createStub(StatRepository::class);
        $statRepository->method('findLatest')->willReturn([]);

        $subject = new LatestSearchesDataProvider($statRepository);

        self::assertSame([], $subject->getRows());
    }
}
