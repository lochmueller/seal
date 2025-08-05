<?php

namespace Lochmueller\Seal\EventListener;

use Lochmueller\Index\Event\IndexFileEvent;
use Lochmueller\Index\Event\IndexPageEvent;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Lochmueller\Seal\Seal;
use TYPO3\CMS\Core\Attribute\AsEventListener;

readonly class IndexEventListener
{
    public function __construct(
        private Seal $seal,
    )
    {
    }

    #[AsEventListener('seal-index')]
    public function __invoke(IndexFileEvent|IndexPageEvent $event): void
    {
        try {
            $engine = $this->seal->buildEngineBySite($event->site);

            $document = [
                'site' => $event->site->getIdentifier(),
                #'title' => $event->title,
                #'content' => $event->content,

                #'id' => (int) $pageInformation->getId(),
                #'access' => implode(',', $this->context->getPropertyFromAspect('frontend.user', 'groupIds', [0, -1])),
                #'title' => $this->pageTitleProviderManager->getTitle($request),
                #'content' => strip_tags($tsfe->content),
                #'preview' => '',
                #'uri' => 'https://www.google.de', // @todo perhaps only URI params (check URI building in frontend process)
            ];

            $engine->saveDocument(SchemaBuilder::DEFAULT_INDEX, $document);
        } catch (\Exception $exception) {

        }

    }

}