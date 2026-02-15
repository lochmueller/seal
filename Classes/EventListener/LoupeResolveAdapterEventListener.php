<?php

declare(strict_types=1);

namespace Lochmueller\Seal\EventListener;

use CmsIg\Seal\Adapter\Loupe\LoupeAdapterFactory;
use Lochmueller\Seal\Event\ResolveAdapterEvent;
use Lochmueller\Seal\Exception\AdapterDependenciesNotFoundException;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Core\Environment;

/**
 * Example: loupe://var/indices/
 */
class LoupeResolveAdapterEventListener
{
    #[AsEventListener('seal-adapter-loupe')]
    public function resolveAdapter(ResolveAdapterEvent $event): void
    {
        if (!str_starts_with($event->searchDsn->scheme, 'loupe')) {
            return;
        }

        if (!class_exists(LoupeAdapterFactory::class)) {
            throw new AdapterDependenciesNotFoundException(package: 'cmsig/seal-loupe-adapter');
        }

        $directory = Environment::getProjectPath() . '/' . $event->searchDsn->host . '/' . ltrim(($event->searchDsn->path ?? ''), '/');
        $event->adapter = (new LoupeAdapterFactory())->createAdapter(['host' => $directory]);
    }
}
