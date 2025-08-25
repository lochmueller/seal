<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_filter',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'type' => 'type',
        'sortby' => 'sorting',
        'hideTable' => true,
        'delete' => 'deleted',
        'default_sortby' => 'ORDER BY crdate',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
    ],
    // @todo add filter
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
];