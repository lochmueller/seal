<?php

declare(strict_types=1);

/*
 * typo3/cms-dashboard is an optional integration of EXT:seal (composer "suggest"), so the
 * widget interfaces are not always available during the static analysis. In that case the
 * same stub the unit tests use is loaded, so PHPStan is able to reflect the data providers.
 */
if (!interface_exists(\TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface::class)) {
    require __DIR__ . '/../Tests/Unit/Dashboard/Provider/Fixtures/ListDataProviderInterface.php';
}
