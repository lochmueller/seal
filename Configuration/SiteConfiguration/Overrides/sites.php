<?php

declare(strict_types=1);

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

$GLOBALS['SiteConfiguration']['site']['columns']['sealPaginationClass'] = [
    'label' => $lll . 'site.sealPaginationClass',
    'description' => $lll . 'site.sealPaginationClass.description',
    'config' => [
        'type' => 'input',
        'eval' => 'trim',
        'default' => \TYPO3\CMS\Core\Pagination\SimplePagination::class,
    ],
];

$GLOBALS['SiteConfiguration']['site']['columns']['sealPaginationMaximumNumberOfLinks'] = [
    'label' => $lll . 'site.sealPaginationMaximumNumberOfLinks',
    'description' => $lll . 'site.sealPaginationMaximumNumberOfLinks.description',
    'config' => [
        'type' => 'number',
        'eval' => 'trim',
        'default' => '6',
    ],
];

$showitem = $GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] ?? '';
$sealFields = '--div--;' . $lll . 'seal, sealSearchDsn, sealAutocompleteMinCharacters, sealItemsPerPage, sealPaginationClass, sealPaginationMaximumNumberOfLinks';

if (str_contains($showitem, 'languages')) {
    $showitem = (string) preg_replace(
        '/\blanguages\b,?/',
        '$0, ' . $sealFields . ',',
        $showitem,
        1,
    );
} else {
    $showitem = rtrim($showitem, ', ') . ', ' . $sealFields;
}

$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] = $showitem;
