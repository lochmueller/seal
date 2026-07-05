<?php

declare(strict_types=1);

namespace Lochmueller\Seal\EventListener;

use Lochmueller\Index\Event\DeletePageEvent;
use Lochmueller\Seal\Event\BeforePageDeleteEvent;
use Lochmueller\Seal\Seal;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;

final class DeleteDocumentEventListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly Seal $seal,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    #[AsEventListener('seal-delete-page-from-index')]
    public function __invoke(DeletePageEvent $event): void
    {
        try {
            $engine = $this->seal->buildEngineBySite($event->site);
            $identifier = 'p-' . md5($event->uri);

            $beforePageDeleteEvent = new BeforePageDeleteEvent(
                documentIdentifier: $identifier,
                uri: $event->uri,
                site: $event->site,
                indexName: $this->seal->getIndexNameBySite($event->site)
            );

            $this->eventDispatcher->dispatch($beforePageDeleteEvent);

            if ($beforePageDeleteEvent->deletePage) {
                $engine->deleteDocument($beforePageDeleteEvent->indexName, $beforePageDeleteEvent->documentIdentifier);
            }
        }  catch (\Exception $exception) {
            $this->logger?->error($exception->getMessage(), ['exception' => $exception]);
        }
    }
}
