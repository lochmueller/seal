<?php

declare(strict_types=1);

namespace Lochmueller\Seal\EventListener\ResolveAdapter;

use Lochmueller\Seal\Adapter\Typo3Adapter;
use Lochmueller\Seal\Event\ResolveAdapterEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Example: typo3://localhost or empty
 */
class Typo3ResolveAdapterEventListener
{
    public function __construct(
        protected Typo3Adapter $typo3Adapter,
    ) {}

    #[AsEventListener('seal-adapter-typo3')]
    public function indexPageContent(ResolveAdapterEvent $event): void
    {
        if ($event->searchDsn->type === 'typo3' || $event->searchDsn->type === null) {
            $event->adapter = $this->typo3Adapter;
        }
    }
}
