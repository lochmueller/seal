<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Dashboard\Provider;

use Lochmueller\Seal\Repository\StatRepository;

class TopSearchesDataProvider implements TableDataProviderInterface
{
    public function __construct(
        private readonly StatRepository $statRepository,
    ) {}

    public function getHeaders(): array
    {
        return [
            'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:dashboard.column.searchTerm',
            'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:dashboard.column.site',
            'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:dashboard.column.count',
        ];
    }

    public function getRows(): array
    {
        $rows = [];
        foreach ($this->statRepository->findTopSearchesOfCurrentMonth() as $row) {
            $rows[] = [
                (string) $row['search_term'],
                (string) $row['site'],
                (string) $row['count'],
            ];
        }

        return $rows;
    }
}
