<?php

defined('TYPO3') or die();

use Lochmueller\Seal\Controller\SearchController;
use Lochmueller\Seal\Queue\Message\WebIndexMessage;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionUtility::configurePlugin(
    'Seal',
    'Search',
    [SearchController::class => 'search,list'],
    [SearchController::class => 'search,list'],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

// Indexing is always async
$GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'][WebIndexMessage::class] = 'doctrine';