<?php

declare(strict_types=1);

namespace Lochmueller\Seal\EventListener;

use DateTimeImmutable;
use Lochmueller\Index\Event\IndexFileEvent;
use Lochmueller\Index\Event\IndexPageEvent;
use Lochmueller\Index\Event\IndexRecordReadyEvent;
use Lochmueller\Index\Traversing\RecordSelection;
use Lochmueller\Seal\Event\BeforeSaveDocumentEvent;
use Lochmueller\Seal\Event\Enum\IndexType;
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
    public function __invoke(IndexFileEvent|IndexPageEvent|IndexRecordReadyEvent $event): void
    {
        try {
            $this->logger?->info('Start index handling of EXT:seal', ['event' => $event::class]);
            $engine = $this->seal->buildEngineBySite($event->site);

            $preview = '';
            $size = 0;
            $extension = '';
            $uri = $event instanceof IndexRecordReadyEvent ? '' : $event->uri;
            $indexType = null;
            $uid = 0;
            $recordFields = [];

            if ($event instanceof IndexFileEvent && $event->fileIdentifier !== '') {
                $indexType = IndexType::File;
                try {
                    $file = $this->resourceFactory->getFileObjectFromCombinedIdentifier($event->fileIdentifier);
                    if ($file !== null) {
                        $uid = $file->getUid();
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
            } elseif ($event instanceof IndexRecordReadyEvent) {
                $indexType = IndexType::Record;
                $uid = $event->recordUid;
                $recordFields = $event->fields;
            } elseif ($event instanceof IndexPageEvent && $event->site instanceof Site) {
                $indexType = IndexType::Page;
                $uid = $event->pageUid;

                if ($uri === '') {
                    $arguments = [];
                    try {
                        $language = $event->site->getLanguageById($event->language);
                        $arguments['_language'] = $language;
                    } catch (\InvalidArgumentException $e) {
                    }
                    $uri = (string) $event->site->getRouter()->generateUri($event->pageUid, $arguments);
                }
            }

            $language = $event instanceof IndexFileEvent ? 0 : $event->language;
            $title = $event instanceof IndexRecordReadyEvent
                ? (is_scalar($recordFields['title'] ?? null) ? (string) $recordFields['title'] : $event->table . ' ' . $uid)
                : $event->title;
            $content = $event instanceof IndexRecordReadyEvent
                ? (is_scalar($recordFields['content'] ?? null) ? (string) $recordFields['content'] : '')
                : $event->content;
            if ($event instanceof IndexRecordReadyEvent) {
                $recordFields['title'] = $title;
                $recordFields['content'] = $content;
            }
            $identifier = match (true) {
                $event instanceof IndexRecordReadyEvent => 'r-' . $event->table . '-' . $uid . '-' . $language,
                $event instanceof IndexPageEvent => 'p-' . md5($uri),
                default => 'd-' . md5($uri),
            };

            $document = [
                'id' => $identifier,
                'site' => $event->site->getIdentifier(),
                'language' => (string) $language,
                'uri' => $uri,
                'indexdate' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM),
                'title' => $title,
                'content' => $this->toPlainText($content),
                'tags' => $this->getTags($event),
                'size' => $size,
                'extension' => $extension,
                'preview' => $preview,
            ];
            $document = array_replace($document, $recordFields);

            $beforeSaveEvent = new BeforeSaveDocumentEvent($document, $event->site, $this->seal->getIndexNameBySite($event->site), $indexType, $uid);
            $this->eventDispatcher->dispatch($beforeSaveEvent);

            $this->logger?->info('Save document to EXT:seal engine', ['engine' => $engine]);
            $engine->saveDocument($beforeSaveEvent->indexName, $beforeSaveEvent->document);
        } catch (\Exception $exception) {
            $this->logger?->error($exception->getMessage(), ['exception' => $exception]);
            if ($event instanceof IndexRecordReadyEvent) {
                throw $exception;
            }
        }

    }

    /**
     * @return array<int, string>
     */
    /**
     * Block-level elements separate words. strip_tags() alone would glue
     * "<h1>Title</h1><p>Text</p>" into "TitleText", so a space is inserted
     * after every closing block tag (and after <br>/<hr>) before the tags
     * are removed. Whitespace is collapsed afterwards as before.
     */
    protected function toPlainText(string $html): string
    {
        $html = (string) preg_replace(
            '#</(?:p|div|h[1-6]|li|ul|ol|dl|dt|dd|td|th|tr|table|thead|tbody|blockquote|pre|section|article|header|footer|nav|aside|figure|figcaption|address)>|<(?:br|hr)\s*/?>#i',
            '$0 ',
            $html,
        );

        return trim((string) preg_replace('/\s+/', ' ', strip_tags($html)));
    }

    /**
     * @return array<int, string>
     */
    protected function getTags(IndexFileEvent|IndexPageEvent|IndexRecordReadyEvent $event): array
    {
        $tags = [];
        $tags[] = match (true) {
            $event instanceof IndexRecordReadyEvent => 'Record',
            $event instanceof IndexPageEvent => 'Page',
            default => 'File',
        };
        if ($event instanceof IndexRecordReadyEvent) {
            $tags[] = $event->table;
        }
        if ($event instanceof IndexPageEvent && $event->pageUid) {
            $row = $this->recordSelection->findRenderablePage($event->pageUid, $event->language);
            if (isset($row['keywords'])) {
                $tags = array_merge($tags, GeneralUtility::trimExplode(',', $row['keywords'], true));
            }
        }
        return $tags;
    }

}
