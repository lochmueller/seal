<?php

$lll = 'LLL:EXT:seal/Resources/Private/Language/locallang.xlf:';

$GLOBALS['SiteConfiguration']['site']['columns']['sealSearchDsn'] = [
    'label' => $lll . 'site.sealSearchDsn',
    'description' => $lll . 'site.sealSearchDsn.description',
    'config' => [
        'type' => 'input',
        'eval' => 'trim',
        'default' => 'typo3://',
    ],
];

$GLOBALS['SiteConfiguration']['site']['columns']['sealAutocompleteMinCharacters'] = [
    'label' => $lll . 'site.sealAutocompleteMinCharacters',
    'description' => $lll . 'site.sealAutocompleteMinCharacters.description',
    'config' => [
        'type' => 'number',
        'eval' => 'trim',
        'default' => '3',
    ],
];

$GLOBALS['SiteConfiguration']['site']['columns']['sealItemsPerPage'] = [
    'label' => $lll . 'site.sealItemsPerPage',
    'description' => $lll . 'site.sealItemsPerPage.description',
    'config' => [
        'type' => 'number',
        'eval' => 'trim',
        'default' => '10',
    ],
];

$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] = str_replace(
    ', languages,',
    ', languages, --div--;' . $lll . 'seal, sealSearchDsn, sealAutocompleteMinCharacters,sealItemsPerPage,',
    $GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'],
);
