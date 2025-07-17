<?php

$GLOBALS['SiteConfiguration']['site']['columns']['sealSearchDsn'] = [
    'label' => 'Search DSN',
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

$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] = str_replace(
    ', languages,',
    ', languages, --div--;SEAL / Search, sealSearchDsn, sealAutocompleteMinCharacters,',
    $GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'],
);