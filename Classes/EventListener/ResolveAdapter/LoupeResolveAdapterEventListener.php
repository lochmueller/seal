<?php

declare(strict_types=1);

namespace Lochmueller\Seal\EventListener\ResolveAdapter;

use CmsIg\Seal\Adapter\Loupe\LoupeAdapterFactory;
use Lochmueller\Seal\Event\ResolveAdapterEvent;
use Lochmueller\Seal\Exception\AdapterDependenciesNotFoundException;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Core\Environment;

/**
 * Example: loupe://varPath/folderName
 * @todo move to Interface and offical Factory
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

        if (!class_exists(LoupeAdapterFactory::class)) {
            throw new AdapterDependenciesNotFoundException(package: 'cmsig/seal-loupe-adapter');
        }

        $directory = $event->searchDsn->host . ($event->searchDsn->path ?? '');

        foreach ($this->environment->toArray() as $key => $value) {
            if (str_starts_with($directory, $key)) {
                $directory = str_replace($key, $value, $directory);
            }
        }

        $event->adapter = (new LoupeAdapterFactory())->createAdapter(['host' => $directory]);
    }
}
