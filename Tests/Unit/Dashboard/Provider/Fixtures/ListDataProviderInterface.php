<?php

declare(strict_types=1);

namespace TYPO3\CMS\Dashboard\Widgets;

/**
 * Stub interface for the unit tests and the PHPStan analysis, used when the optional
 * typo3/cms-dashboard package is not installed. It mirrors the signature of the original
 * TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface.
 */
interface ListDataProviderInterface
{
    /**
     * Return the items to be shown. This should be an array like ['item 1', 'item 2', 'item 3'].
     */
    public function getItems(): array;
}
