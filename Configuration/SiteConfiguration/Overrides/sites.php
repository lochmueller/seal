<?php

$GLOBALS['SiteConfiguration']['site']['columns']['sealSearchDsn'] = [
    'label' => 'Search Adapter DSN',
    'description' => 'Configure the search adapter via DSN. Examples are "typo3://" or "loupe://var/indices/". Please check the AdapterFactory of the different adapter packages. If a adapter package is installed via composer your could create the adapter via DSN.',
    'config' => [
        'type' => 'input',
        'eval' => 'trim',
        'default' => 'typo3://',
    ],
];

$GLOBALS['SiteConfiguration']['site']['columns']['sealAutocompleteMinCharacters'] = [
    'label' => 'Autocomplete min chars',
    'description' => 'Minimum chars for the auto complete endpoint of this site. Default value is 3.',
    'config' => [
        'type' => 'input',
        'eval' => 'trim',
        'default' => '3',
    ],
];

$GLOBALS['SiteConfiguration']['site']['columns']['sealIndexType'] = [
    'label' => 'Index type',
    'description' => 'Type of the index process.',
    'config' => [
        'type' => 'select',
        'renderType' => 'selectSingle',
        'items' => [
            [
                'label' => 'None',
                'value' => '',
            ],
            [
                'label' => 'Cache Indexing (like EXT:indexed_search)',
                'value' => 'cache',
            ],
            [
                'label' => 'Database indexing (like EXT:ke_search) - Keep in mind: Create the "seal:index" scheduler command.',
                'value' => 'database',
            ],
            [
                'label' => 'Web indexing (like EXT:solr) - Keep in mind: Create the "seal:index" scheduler command and "messenger:consume" with receivers->doctrine command.',
                'value' => 'web',
            ],
        ],
    ],
];

$GLOBALS['SiteConfiguration']['site']['columns']['sealIndexConfiguration'] = [
    'label' => 'Index configuration',
    'description' => 'Configuration of the index process via YAML.',
    'config' => [
        'type' => 'text',
    ],
];

$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] = str_replace(
    ', languages,',
    ', languages, --div--;SEAL / Search, sealIndexType, sealSearchDsn, sealAutocompleteMinCharacters,sealIndexConfiguration,',
    $GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'],
);
