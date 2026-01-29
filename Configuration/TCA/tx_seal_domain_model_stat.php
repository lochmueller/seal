<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_stat',
        'iconfile' => 'EXT:seal/Resources/Public/Icons/Extension.png',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'default_sortby' => 'ORDER BY crdate',
        'rootLevel' => '1',
    ],
    'columns' => [
        'site' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_stat.site',
            'config' => [
                'type' => 'input',
                'size' => '40',
            ],
        ],
        'language' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_stat.language',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
        'search_term' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_stat.search_term',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'site,language,search_term'],
    ],
];
