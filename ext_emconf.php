<?php

/** @var string $_EXTKEY */
$EM_CONF[$_EXTKEY] = [
    'title' => 'Search Engine Abstraction Layer',
    'description' => 'SEAL Search - Flexible integration of the Search Engine Abstraction Layer project',
    'version' => '0.0.2',
    'category' => 'fe',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
            'index' => '1.0.0-1.99.99',
            'php' => '8.3.0-8.99.99',
        ],
    ],
    'state' => 'stable',
    'author' => 'Tim Lochmüller',
    'author_email' => 'tim@fruit-lab.de',
    'author_company' => 'HDNET GmbH & Co. KG',
    'autoload' => [
        'psr-4' => [
            'Lochmueller\\Seal\\' => 'Classes',
        ],
    ],
];