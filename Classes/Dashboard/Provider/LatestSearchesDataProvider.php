<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Dashboard\Provider;

use Lochmueller\Seal\Repository\StatRepository;
use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;

class LatestSearchesDataProvider implements ListDataProviderInterface
{
    public function __construct(
        private readonly StatRepository $statRepository,
    ) {}

    /**
     * @return array<int, array{string, string, string, string}>
     */
    public function getItems(): array
    {
        $rows = $this->statRepository->findLatest();

        if ($rows === []) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = implode(' / ', [
                (string) $row['search_term'],
                (string) $row['site'],
                (string) $row['language'],
                date('Y-m-d H:i', (int) $row['crdate']),
            ]);
        }

        return $items;
    }
}
