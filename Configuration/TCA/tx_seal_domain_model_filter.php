<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_filter',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'type' => 'type',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'default_sortby' => 'ORDER BY crdate',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
    ],
    'columns' => [
        'title' => [
            'exclude' => 0,
            'title' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_filter.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
        'type' => [
            'exclude' => 0,
            'l10n_display' => 'defaultAsReadonly',
            'displayCond' => 'FIELD:l10n_parent:<:1',
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_filter.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_filter.type.searchCondition',
                        'value' => 'searchCondition',
                    ],
                    [
                        'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_filter.type.tagCondition',
                        'value' => 'tagCondition',
                    ],
                    [
                        'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_filter.type.geoDistanceCondition',
                        'value' => 'geoDistanceCondition',
                    ],
                ],
                'size' => 1,
                'maxitems' => 1,
                'default' => 'searchCondition',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'title,type'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];