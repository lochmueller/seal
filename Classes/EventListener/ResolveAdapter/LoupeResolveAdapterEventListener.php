<?php

declare(strict_types=1);

namespace Lochmueller\Seal\EventListener\ResolveAdapter;

use CmsIg\Seal\Adapter\Loupe\LoupeAdapter;
use CmsIg\Seal\Adapter\Loupe\LoupeHelper;
use Lochmueller\Seal\Event\ResolveAdapterEvent;
use Lochmueller\Seal\Exception\AdapterDependenciesNotFoundException;
use Lochmueller\Seal\Exception\AdapterNotFoundException;
use Loupe\Loupe\LoupeFactory;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Core\Environment;

/**
 * Example: loupe://localhost/varPath/folderName
 */
class LoupeResolveAdapterEventListener
{
    public function __construct(
        protected Environment $environment,
    ) {}

    #[AsEventListener('seal-adapter-loupe')]
    public function indexPageContent(ResolveAdapterEvent $event): void
    {

        if ($event->searchDsn->type !== 'loupe') {
            return;
        }

        if (!class_exists(LoupeAdapter::class)) {
            throw new AdapterDependenciesNotFoundException(package: 'cmsig/seal-loupe-adapter');
        }
        $directory = $searchDsnDto->path ?? 'varPath';

        foreach ($this->environment->toArray() as $key => $value) {
            if (str_starts_with($directory, $key)) {
                $directory = str_replace($key, $value, $directory);
            }

        }

        $event->adapter = new LoupeAdapter(
            new LoupeHelper(
                new LoupeFactory(),
                $directory,
            ),
        );
    }
}
