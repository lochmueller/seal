<?php

$GLOBALS['SiteConfiguration']['site']['columns']['seal_search_dsn'] = [
    'label' => 'Search DSN',
    'config' => [
        'type' => 'input',
        'eval' => 'trim',
        'default' => 'typo3://localhost',
    ],
];

$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] = str_replace(
    ', languages,',
    ', languages, --div--;SEAL / Search, seal_search_dsn, ',
    $GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'],
);