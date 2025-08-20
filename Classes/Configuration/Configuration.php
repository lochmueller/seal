<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Configuration;

class Configuration
{
    public function __construct(
        public readonly int $autocompleteMinCharacters,
        public readonly int $itemsPerPage,
    ) {}

    public static function createByArray(array $configuration): Configuration
    {
        return new self(
            autocompleteMinCharacters: (int) ($configuration['sealAutocompleteMinCharacters'] ?? 3),
            itemsPerPage: (int) ($configuration['sealItemsPerPage'] ?? 10),
        );
    }

}
