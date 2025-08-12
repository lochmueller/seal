<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addPlugin(
    [
        'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:plugin.label',
        'description' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:plugin.description',
        'value' => 'seal_search',
        'icon'  => 'ext-seal-icon',
        'group' => 'Seal',
    ],
    'CType',
    'seal'
);