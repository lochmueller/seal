<?php

defined('TYPO3') or die();

use Lochmueller\Seal\Controller\SearchController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionUtility::configurePlugin(
    'Seal',
    'Search',
    [SearchController::class => 'search,list'],
    [SearchController::class => 'search,list'],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);
