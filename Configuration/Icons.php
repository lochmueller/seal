<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;

return [
    'ext-seal-icon' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:seal/Resources/Public/Icons/Extension.png',
    ],
];
