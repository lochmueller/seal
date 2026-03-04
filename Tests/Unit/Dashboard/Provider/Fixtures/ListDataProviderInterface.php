<?php

declare(strict_types=1);

namespace TYPO3\CMS\Dashboard\Widgets;

/**
 * Stub interface for testing when typo3/cms-dashboard is not installed.
 */
interface ListDataProviderInterface
{
    /**
     * @return array<int, array<int, string>>
     */
    public function getItems(): array;
}
