<?php

declare(strict_types=1);

namespace Lochmueller\Seal\EventListener;

use DateTimeImmutable;
use Lochmueller\Index\Event\IndexFileEvent;
use Lochmueller\Index\Event\IndexPageEvent;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Lochmueller\Seal\Seal;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;

class IndexEventListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly Seal            $seal,
        private readonly ResourceFactory $resourceFactory,
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
                } catch (\Exception $exception) {

                }
            } elseif ($event instanceof IndexPageEvent && $uri === '') {
                $uri = (string) $event->site->getRouter()->generateUri($event->pageUid);
            }

            $document = [
                'id' => $event instanceof IndexPageEvent ? 'p-' . md5($uri) : 'd-' . md5($uri),
                'site' => $event->site->getIdentifier(),
                'title' => $event->title,
                'content' => preg_replace('/\\s+/', ' ', strip_tags($event->content)),
                'language' => isset($event->language) ? (string) $event->language : '0',
                'uri' => $uri,
                'extension' => $extension,
                'size' => $size,
                'index_date' => (new \DateTimeImmutable())->format(DateTimeImmutable::ATOM),
                #'access' => implode(',', $this->context->getPropertyFromAspect('frontend.user', 'groupIds', [0, -1])),
                'preview' => $preview,
            ];

            $engine->saveDocument(SchemaBuilder::DEFAULT_INDEX, $document);
        } catch (\Exception $exception) {
            $this->logger?->error($exception->getMessage(), ['exception' => $exception]);
        }

    }

}
