<?php

defined('TYPO3') or die();

use Lochmueller\Seal\Controller\SearchController;
use Lochmueller\Seal\Controller\StartController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionUtility::configurePlugin(
    'Seal',
    'Search',
    [
        StartController::class => 'start',
        SearchController::class => 'search',
    ],
    [
        SearchController::class => 'search'
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);
