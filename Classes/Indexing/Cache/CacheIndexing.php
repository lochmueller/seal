<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Indexing\Cache;

use Lochmueller\Seal\Indexing\IndexingInterface;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Lochmueller\Seal\Seal;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\PageTitle\PageTitleProviderManager;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

class CacheIndexing implements IndexingInterface
{
    public function __construct(
        private Seal                     $seal,
        private Context                  $context,
        private PageTitleProviderManager $pageTitleProviderManager,
    ) {}

    public function indexPageContentViaAfterCacheableContentIsGeneratedEvent(AfterCacheableContentIsGeneratedEvent $event): void
    {
        if (!$event->isCachingEnabled()) {
            return;
        }

        $request = $event->getRequest();
        $tsfe = $request->getAttribute('frontend.controller');
        /** @var Site $site */
        $site = $request->getAttribute('site');
        $indexType = $site->getConfiguration()['sealIndexType'] ?? '';
        if ($indexType !== 'cache') {
            return;
        }

        $pageInformation = $request->getAttribute('frontend.page.information');
        $pageRecord = $pageInformation->getPageRecord();

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
            'preview' => '',
            'uri' => 'https://www.google.de', // @todo perhaps only URI params (check URI building in frontend process)
        ];

        $this->seal->buildEngineBySite($site)->saveDocument(SchemaBuilder::DEFAULT_INDEX, $page);
    }
}
