<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Event;

use TYPO3\CMS\Core\Site\Entity\SiteInterface;

final class BeforeSaveDocumentEvent
{
    /**
     * @param array<string, mixed> $document
     */
    public function __construct(
        public array $document,
        public readonly SiteInterface $site,
        public readonly string $indexName,
    ) {}
}
