<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Schema;

use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use Lochmueller\Seal\Event\BuildSchemaEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class SchemaBuilder
{
    public const DEFAULT_INDEX = 'page';

    public function __construct(protected EventDispatcherInterface $eventDispatcher) {}

    public function getSchema(): Schema
    {
        $schema = new Schema([
            self::DEFAULT_INDEX => $this->getPageIndex(),
        ]);

        $event = new BuildSchemaEvent($schema);
        return $this->eventDispatcher->dispatch($event)->schema;
    }

    public function getPageIndex(): Index
    {
        return new Index(SchemaBuilder::DEFAULT_INDEX, [
            'id' => new Field\IdentifierField('id'), // Page ID or PageID incl. suffix and record ID. Example: 128 or 291-tx_news-12839
            'language' => new Field\IntegerField('language'), // Language UID. Example: 129
            'site' => new Field\TextField('site', searchable: false), // Site identifier. Example: portal
            'title' => new Field\TextField('title', sortable: true), // Title. Example: Homepage - My Company
            'tags' => new Field\TextField('tags', multiple: true, filterable: true), // @todo handle format // default tags are. "page", "file", "plugin", "tx_news"
            'content' => new Field\TextField('content'), // Content. Example: I am a long string example
            'extension' => new Field\TextField('extension'), // File extension. Example: html, pdf, png
        ]);
    }
}
