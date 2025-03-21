<?php

declare(strict_types=1);

namespace Lochmueller\Seal\EventListener;


use Lochmueller\Seal\Indexing\Cache\Indexing;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\PageTitle\PageTitleProviderManager;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;
use TYPO3\CMS\IndexedSearch\Event\EnableIndexingEvent;
use TYPO3\CMS\IndexedSearch\Indexer;

final class FrontendGenerationPageIndexingTrigger
{


    public function __construct(
        private Indexing $cacheIndexing,
    )
    {
    }

    #[AsEventListener('seal-cache-indexer')]
    public function indexPageContent(AfterCacheableContentIsGeneratedEvent $event): void
    {
        if (!$event->isCachingEnabled()) {
            return;
        }

        $request = $event->getRequest();
        $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
        $pageArguments = $request->getAttribute('routing');
        $pageInformation = $request->getAttribute('frontend.page.information');
        $pageRecord = $pageInformation->getPageRecord();
        $tsfe = $request->getAttribute('frontend.controller');

        if ($pageRecord['no_search'] ?? false) {
            $this->timeTracker->setTSlogMessage('Index page? No, The "No Search" flag has been set in the page properties!');
            return;
        }
        $languageAspect = $this->context->getAspect('language');
        if ($languageAspect->getId() !== $languageAspect->getContentId()) {
            $this->timeTracker->setTSlogMessage(
                'Index page? No, languageId was different from contentId which indicates that the page contains'
                . ' fall-back content and that would be falsely indexed as localized content.'
            );
            return;
        }


        $configuration = [
            // Page id
            'id' => $pageInformation->getId(),
            // Page type
            'type' => $pageArguments->getPageType(),
            // site language id of the language of the indexing.
            'sys_language_uid' => $languageAspect->getId(),
            // MP variable, if any (Mount Points)
            'MP' => $pageInformation->getMountPoint(),
            // Group list
            'gr_list' => implode(',', $this->context->getPropertyFromAspect('frontend.user', 'groupIds', [0, -1])),
            // page arguments array
            'staticPageArguments' => $pageArguments->getStaticArguments(),
            // The creation date of the TYPO3 page
            'crdate' => $pageRecord['crdate'],
            'content' => $tsfe->content,
            // Alternative title for indexing
            'indexedDocTitle' => $this->pageTitleProviderManager->getTitle($request),
            // Most recent modification time (seconds) of the content on the page. Used to evaluate whether it should be re-indexed.
            'mtime' => $tsfe->register['SYS_LASTCHANGED'] ?? $pageRecord['SYS_LASTCHANGED'],
            // Whether to index external documents like PDF, DOC etc.
            'index_externals' => $typoScriptConfigArray['index_externals'] ?? true,
            // Length of description text (max 250, default 200)
            'index_descrLgd' => $typoScriptConfigArray['index_descrLgd'] ?? 0,
            'index_metatags' => $typoScriptConfigArray['index_metatags'] ?? true,
            // Set to zero (@todo: why is this needed?)
            'recordUid' => 0,
            'freeIndexUid' => 0,
            'freeIndexSetId' => 0,
        ];

        // Call Cache Indexer
    }
}

