<?php

return [
    'ctrl' => [
        'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default',
        'title' => 'title',
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
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default.id',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
        'content' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default.content',
            'config' => [
                'type' => 'text',
            ],
        ],
        'index_date' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default.index_date',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'dbType' => 'datetime',
            ],
        ],
        'language' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default.language',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
        'site' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default.site',
            'config' => [
                'type' => 'input',
                'size' => '40',
            ],
        ],
        'tags' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default.tags',
            'config' => [
                'type' => 'text',
            ],
        ],
        'preview' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default.preview',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
        'uri' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default.uri',
            'config' => [
                'type' => 'input',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'id,title,content,language,site,preview,uri,index_date'],
    ],
];