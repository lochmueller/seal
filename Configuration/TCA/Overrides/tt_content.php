<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addPlugin(
    [
        'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:plugin.label',
        'description' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:plugin.description',
        'value' => 'seal_search',
        'icon' => 'ext-seal-icon',
        'group' => 'Seal',
    ],
    'CType',
    'seal'
);

$newCols = [
    'seal_filter' => [
        'label' => 'Search Filter',
        'config' => [
            'type' => 'inline',
            'allowed' => 'tx_seal_domain_model_filter',
            'foreign_table' => 'tx_seal_domain_model_filter',
            'foreign_field' => 'parent',
            'minitems' => 1,
            'maxitems' => 100,
            'appearance' => [
                'collapseAll' => true,
                'expandSingle' => true,
                'levelLinksPosition' => 'bottom',
                'useSortable' => true,
                'showPossibleLocalizationRecords' => true,
                'showAllLocalizationLink' => true,
                'showSynchronizationLink' => true,
                'enabledControls' => [
                    'info' => false,
                ],
            ],
            'behaviour' => [
                'allowLanguageSynchronization' => true,
            ],
        ],
    ],
];

ExtensionManagementUtility::addTCAcolumns('tt_content', $newCols);

ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    'seal_filter',
    'seal_search',
    'after:general'
);
