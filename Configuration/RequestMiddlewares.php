<?php

declare(strict_types=1);


use Lochmueller\Seal\Middleware\AutocompleteMiddleware;

return [
    'frontend' => [
        'seal/autocomplete' => [
            'target' => AutocompleteMiddleware::class,
            'before' => [
                'typo3/cms-frontend/backend-user-authentication',
            ],
            'after' => [
                'typo3/cms-frontend/site',
            ],
        ],
    ],
];
