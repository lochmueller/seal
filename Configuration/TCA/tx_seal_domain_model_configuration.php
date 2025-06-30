<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_configuration',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'default_sortby' => 'ORDER BY crdate',
        'enablecolumns' => [
            'fe_group' => 'fe_group',
        ],
    ],
    'columns' => [
        'title' => [
            'exclude' => 0,
            'title' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_configuration.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
        // type, storage
    ],
    'types' => [
        '0' => ['showitem' => 'title'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];