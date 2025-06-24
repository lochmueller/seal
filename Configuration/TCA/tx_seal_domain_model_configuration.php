<?php

return [
    'ctrl' => [
        'title' => 'Configuration',  # @todo LLL
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
        // type, storage
    ],
    'types' => [
        '0' => ['showitem' => 'title'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];