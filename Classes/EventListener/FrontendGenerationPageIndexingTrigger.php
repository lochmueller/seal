<?php

declare(strict_types=1);

namespace Lochmueller\Seal\EventListener;

use Lochmueller\Seal\Schema\SchemaBuilder;
use Lochmueller\Seal\Seal;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\PageTitle\PageTitleProviderManager;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

class FrontendGenerationPageIndexingTrigger
{
    public function __construct(
        private Seal                     $seal,
        private Context                  $context,
        private PageTitleProviderManager $pageTitleProviderManager,
    ) {}

    #[AsEventListener('seal-cache-indexer')]
    public function indexPageContent(AfterCacheableContentIsGeneratedEvent $event): void
    {
        if (!$event->isCachingEnabled()) {
            return;
        }

        $request = $event->getRequest();
        $pageInformation = $request->getAttribute('frontend.page.information');
        $pageRecord = $pageInformation->getPageRecord();
        $tsfe = $request->getAttribute('frontend.controller');
        $site = $request->getAttribute('site');

        if ($pageRecord['no_search'] ?? false) {
            return;
        }
        $languageAspect = $this->context->getAspect('language');
        if ($languageAspect->getId() !== $languageAspect->getContentId()) {
            // Index page? No, languageId was different from contentId which indicates that the page contains fall-back
            // content and that would be falsely indexed as localized content.
            return;
        }

        $page = [
            'id' => (int) $pageInformation->getId(),
            'language' => (int) $languageAspect->getId(),
            'site' => $site->getIdentifier(),
            'access' => implode(',', $this->context->getPropertyFromAspect('frontend.user', 'groupIds', [0, -1])),
            'title' => $this->pageTitleProviderManager->getTitle($request),
            'content' => strip_tags($tsfe->content),
        ];

        $this->seal->buildEngineBySite($site)->saveDocument(SchemaBuilder::DEFAULT_INDEX, $page);
    }
}
