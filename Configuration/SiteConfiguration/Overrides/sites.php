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

$GLOBALS['SiteConfiguration']['site']['columns']['sealHighlighting'] = [
    'label' => $lll . 'site.sealHighlighting',
    'description' => $lll . 'site.sealHighlighting.description',
    'config' => [
        'type' => 'check',
        'renderType' => 'checkboxToggle',
        'default' => 1,
        'items' => [
            [
                'label' => '',
            ],
        ],
    ],
];

$GLOBALS['SiteConfiguration']['site']['columns']['sealHighlightingFields'] = [
    'label' => $lll . 'site.sealHighlightingFields',
    'description' => $lll . 'site.sealHighlightingFields.description',
    'config' => [
        'type' => 'input',
        'eval' => 'trim',
        'default' => 'title,content',
    ],
];

$showitem = $GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] ?? '';
$sealFields = '--div--;' . $lll . 'seal, sealSearchDsn, sealAutocompleteMinCharacters, sealItemsPerPage, sealPaginationClass, sealPaginationMaximumNumberOfLinks, sealHighlighting, sealHighlightingFields';

// Add the SEAL fields as own tab right after the languages tab. The word boundary must not match
// the "languages" part of the tab label (e.g. "...tca.xlf:site.tab.languages"), otherwise the
// languages field would lose its own tab and end up below the SEAL fields.
$showitem = (string) preg_replace(
    '/(?<![\w.\-:])languages(?![\w.\-])\s*,/',
    '$0 ' . $sealFields . ',',
    $showitem,
    1,
    $count,
);

if (!$count) {
    $showitem = rtrim($showitem, ", \n\r\t") . ', ' . $sealFields;
}

$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] = $showitem;
