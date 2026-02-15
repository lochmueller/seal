<?php

declare(strict_types=1);

namespace Lochmueller\Seal\EventListener;

use DateTimeImmutable;
use Lochmueller\Index\Event\IndexFileEvent;
use Lochmueller\Index\Event\IndexPageEvent;
use Lochmueller\Index\Traversing\RecordSelection;
use Lochmueller\Seal\Event\BeforeSaveDocumentEvent;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Lochmueller\Seal\Seal;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;

class IndexEventListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly Seal            $seal,
        private readonly ResourceFactory $resourceFactory,
        private readonly RecordSelection $recordSelection,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    #[AsEventListener('seal-index')]
    public function __invoke(IndexFileEvent|IndexPageEvent $event): void
    {
        try {
            $engine = $this->seal->buildEngineBySite($event->site);

            $preview = '';
            $size = 0;
            $extension = '';
            $uri = $event->uri;

            if ($event instanceof IndexFileEvent && isset($event->fileIdentifier)) {

                try {
                    $file = $this->resourceFactory->getFileObjectFromCombinedIdentifier($event->fileIdentifier);
                    if ($file !== null) {
                        $size = $file->getSize();
                        $extension = $file->getExtension();
                        $uri = $event->site->getBase() . $file->getPublicUrl();
                        $shouldRenderPreview = GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], strtolower($file->getExtension()));

                        if ($shouldRenderPreview) {
                            $imageService = GeneralUtility::makeInstance(ImageService::class);
                            $image = $imageService->getImage('', $file, false);
                            $processedImage = $imageService->applyProcessingInstructions($image, [
                                'maxWidth' => 200,
                                'maxHeight' => 200,
                            ]);

                            $preview = $event->site->getBase() . $processedImage->getPublicUrl();
                        }
                    }
                } catch (\Exception $exception) {
                    $this->logger?->error($exception->getMessage(), ['exception' => $exception]);
                }
            } elseif ($event instanceof IndexPageEvent && $uri === '' && $event->site instanceof Site) {
                $uri = (string) $event->site->getRouter()->generateUri($event->pageUid);
            }

            $document = [
                'id' => $event instanceof IndexPageEvent ? 'p-' . md5($uri) : 'd-' . md5($uri),
                'site' => $event->site->getIdentifier(),
                'language' => isset($event->language) ? (string) $event->language : '0',
                'uri' => $uri,
                'indexdate' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM),
                'title' => $event->title,
                'content' => (string) preg_replace('/\\s+/', ' ', strip_tags($event->content)),
                'tags' => $this->getTags($event),
                'size' => $size,
                'extension' => $extension,
                'preview' => $preview,
            ];

            $beforeSaveEvent = new BeforeSaveDocumentEvent($document, $event->site, SchemaBuilder::DEFAULT_INDEX);
            $this->eventDispatcher->dispatch($beforeSaveEvent);

            $engine->saveDocument(SchemaBuilder::DEFAULT_INDEX, $beforeSaveEvent->document);
        } catch (\Exception $exception) {
            $this->logger?->error($exception->getMessage(), ['exception' => $exception]);
        }

    }

    /**
     * @return array<int, string>
     */
    protected function getTags(IndexFileEvent|IndexPageEvent $event): array
    {
        $tags = [];
        $tags[] = $event instanceof IndexPageEvent ? 'Page' : 'File';
        if ($event instanceof IndexPageEvent && $event->pageUid) {
            $row = $this->recordSelection->findRenderablePage($event->pageUid, $event->language);
            if (isset($row['keywords'])) {
                $tags = array_merge($tags, GeneralUtility::trimExplode(',', $row['keywords'], true));
            }
        }
        return $tags;
    }

}
