<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Configuration;

class Configuration
{
    public function __construct(
        public readonly string $searchDsn,
        public readonly int $autocompleteMinCharacters,
        public readonly int $itemsPerPage,
        public readonly string $paginationClass,
        public readonly int $paginationMaximumNumberOfLinks,
    ) {}

    /**
     * @param array<string, mixed> $configuration
     */
    public static function createByArray(array $configuration): Configuration
    {
        return new self(
            searchDsn: (string) ($configuration['sealSearchDsn'] ?? 'typo3://'),
            autocompleteMinCharacters: (int) ($configuration['sealAutocompleteMinCharacters'] ?? 3),
            itemsPerPage: (int) ($configuration['sealItemsPerPage'] ?? 10),
            paginationClass: (string) ($configuration['sealPaginationClass'] ?? \TYPO3\CMS\Core\Pagination\SimplePagination::class),
            paginationMaximumNumberOfLinks: (int) ($configuration['sealPaginationMaximumNumberOfLinks'] ?? 6),
        );
    }

}
