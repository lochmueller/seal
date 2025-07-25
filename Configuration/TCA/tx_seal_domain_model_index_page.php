<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_page',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'default_sortby' => 'ORDER BY crdate',
        'enablecolumns' => [
            'fe_group' => 'fe_group',
        ],
        'rootLevel' => '1',
    ],
    'columns' => [
        'id' => [
            'exclude' => 0,
            'title' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_page.id',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
        'language' => [
            'exclude' => 0,
            'title' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_page.language',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
        'site' => [
            'exclude' => 0,
            'title' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_page.site',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
        'title' => [
            'exclude' => 0,
            'title' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_page.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
        'tags' => [
            'exclude' => 0,
            'title' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_page.tags',
            'config' => [
                'type' => 'text',
            ],
        ],
        'content' => [
            'exclude' => 0,
            'title' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_page.content',
            'config' => [
                'type' => 'text',
            ],
        ],
        'preview' => [
            'exclude' => 0,
            'title' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_page.preview',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
        'uri' => [
            'exclude' => 0,
            'title' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_page.uri',
            'config' => [
                'type' => 'input',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'title'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];