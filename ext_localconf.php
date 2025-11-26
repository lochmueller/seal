<?php

defined('TYPO3') or die();

use Lochmueller\Seal\Controller\SearchController;
use Lochmueller\Seal\Controller\StartController;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

/** @var Environment $context */
$environment = GeneralUtility::makeInstance(Environment::class);
$level = $environment->getContext()->isDevelopment() ? LogLevel::DEBUG : LogLevel::WARNING;

$GLOBALS['TYPO3_CONF_VARS']['LOG']['Lochmueller']['Seal']['writerConfiguration'] = [
    $level => [
        FileWriter::class => [
            'logFileInfix' => 'seal',
        ],
    ],
];

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


if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][TableGarbageCollectionTask::class]['options']['tables']['tx_seal_domain_model_stat'] ?? false)) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][TableGarbageCollectionTask::class]['options']['tables']['tx_seal_domain_model_stat'] = [
        'dateField' => 'tstamp',
        'expirePeriod' => 30,
    ];
}