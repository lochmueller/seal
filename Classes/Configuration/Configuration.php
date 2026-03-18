<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Configuration;

class Configuration
{
    public function __construct(
        public readonly string $searchDsn = 'typo3://',
        public readonly int $autocompleteMinCharacters = 3,
        public readonly int $itemsPerPage = 10,
        public readonly string $paginationClass = \TYPO3\CMS\Core\Pagination\SimplePagination::class,
        public readonly int $paginationMaximumNumberOfLinks = 6,
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
