<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Dashboard\Provider;

use Lochmueller\Seal\Repository\StatRepository;
use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;

class TopSearchesDataProvider implements ListDataProviderInterface
{
    public function __construct(
        private readonly StatRepository $statRepository,
    ) {}

    /**
     * @return array<int, array{string, string}>
     */
    public function getItems(): array
    {
        $rows = $this->statRepository->findTopSearchesOfCurrentMonth();

        if ($rows === []) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = $row['search_term'] . ' / ' . $row['count'];
        }

        return $items;
    }
}
