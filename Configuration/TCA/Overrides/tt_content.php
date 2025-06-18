<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addPlugin(
    [
        'label' => 'Search', // @todo LLL
        'description' => '', // @todo LLL
        'value' => 'seal_search',
        'icon'  => 'ext-seal-extension-icon',
        'group' => 'Seal',
    ],
    'CType',
    'seal'
);