<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default',
        'iconfile' => 'EXT:seal/Resources/Public/Icons/Extension.png',
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
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default.id',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
        // Meta
        'site' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default.site',
            'config' => [
                'type' => 'input',
                'size' => '40',
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
        'uri' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default.uri',
            'config' => [
                'type' => 'input',
            ],
        ],
        'indexdate' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default.indexdate',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'dbType' => 'datetime',
            ],
        ],
        // Content
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
        'tags' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default.tags',
            'config' => [
                'type' => 'group',
                'foreign_table' => 'tx_seal_domain_model_index_default_tag',
                'allowed' => 'tx_seal_domain_model_index_default_tag',
                'MM' => 'tx_seal_domain_model_index_default_mm_tag',
            ],
        ],
        // File
        'size' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default.size',
            'config' => [
                'type' => 'input',
            ],
        ],
        'extension' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default.extension',
            'config' => [
                'type' => 'input',
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
    ],
    'types' => [
        '0' => ['showitem' => '
            id,
            --div--;LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default.meta,site,language,uri,indexdate,
            --div--;LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default.content,title,content,tags,
            --div--;LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default.file,size,extension,preview'
        ],
    ],
];