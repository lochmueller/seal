<?php

return [
    'ctrl' => [
        'title' => 'Index',  # @todo LLL
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
        'title' => [
            'exclude' => 0,
            'label' => 'Title', # @todo LLL
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
        'content' => [
            'exclude' => 0,
            'label' => 'Content', # @todo LLL
            'config' => [
                'type' => 'text',
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