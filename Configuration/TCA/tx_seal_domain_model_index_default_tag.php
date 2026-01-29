<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default_tag',
        'iconfile' => 'EXT:seal/Resources/Public/Icons/Extension.png',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'default_sortby' => 'ORDER BY title',
        'rootLevel' => '1',
    ],
    'columns' => [
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:tx_seal_domain_model_index_default_tag.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'title'],
    ],
];
