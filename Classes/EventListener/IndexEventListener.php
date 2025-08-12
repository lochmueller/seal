<?php

declare(strict_types=1);

namespace Lochmueller\Seal\EventListener;

use Lochmueller\Index\Event\IndexFileEvent;
use Lochmueller\Index\Event\IndexPageEvent;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Lochmueller\Seal\Seal;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;

readonly class IndexEventListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private Seal $seal,
    ) {}

    #[AsEventListener('seal-index')]
    public function __invoke(IndexFileEvent|IndexPageEvent $event): void
    {
        try {
            $engine = $this->seal->buildEngineBySite($event->site);

            $id = $event instanceof IndexPageEvent ? 'p-' . $event->pageUid : 'd-' . md5($event->fileIdentifier);

            $document = [
                'id' => $id,
                'site' => $event->site->getIdentifier(),
                'title' => $event->title,
                'content' => strip_tags($event->content),
                'language' => isset($event->language) ? (string) $event->language : '0',

                #'access' => implode(',', $this->context->getPropertyFromAspect('frontend.user', 'groupIds', [0, -1])),
                #'preview' => '',
                #'uri' => 'https://www.google.de', // @todo perhaps only URI params (check URI building in frontend process)
            ];

            $engine->saveDocument(SchemaBuilder::DEFAULT_INDEX, $document);
        } catch (\Exception $exception) {
            $this->logger?->error($exception->getMessage(), ['exception' => $exception]);
        }

    }

}
