<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

$pluginKey = ExtensionUtility::registerPlugin(
    'Seal',
    'Search',
    'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:plugin.label',
    'ext-seal-icon',
    'plugins',
    'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:plugin.description',
);

$newCols = [
    'seal_show_initial_results' => [
        'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:plugin.field.seal_show_initial_results.title',
        'description' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:plugin.field.seal_show_initial_results.description',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'items' => [
                [
                    'label' => '',
                ],
            ],
        ],
    ],
    'seal_filter' => [
        'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:plugin.field.seal_filter.title',
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
    '--div--;LLL:EXT:seal/Resources/Private/Language/locallang.xlf:plugin.tab.search,seal_show_initial_results,seal_filter',
    $pluginKey,
    'after:general'
);
