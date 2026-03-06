<?php

declare(strict_types=1);

namespace Lochmueller\Seal\EventListener;

use Lochmueller\Seal\Dto\DsnDto;
use Lochmueller\Seal\Event\AfterDsnParsedEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Core\Environment;

/**
 * Example: loupe://var/indices/
 */
class LoupeDsnPathHandlingEventListener
{
    #[AsEventListener('seal-adapter-loupe')]
    public function __invoke(AfterDsnParsedEvent $event): void
    {
        if (!str_starts_with($event->dsnDto->scheme, 'loupe')) {
            return;
        }

        $directory = Environment::getProjectPath() . '/' . $event->dsnDto->host . '/' . ltrim($event->dsnDto->path ?? '', '/');

        $event->dsnDto = new DsnDto(
            scheme: $event->dsnDto->scheme,
            user: $event->dsnDto->user,
            pass: $event->dsnDto->pass,
            host: $directory,
            port: $event->dsnDto->port,
            path: $event->dsnDto->path,
            query: $event->dsnDto->query,
        );
    }
}
