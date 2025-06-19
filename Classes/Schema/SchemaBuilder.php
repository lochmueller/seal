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
    public function __construct(protected EventDispatcherInterface $eventDispatcher) {}

    public function getSchema(): Schema
    {
        $schema = new Schema([
            'page' => $this->getPageIndex(),
            'document' => $this->getDocumentIndex(),
        ]);

        $event = new BuildSchemaEvent($schema);
        return $this->eventDispatcher->dispatch($event)->schema;
    }

    public function getPageIndex(): Index
    {
        return new Index('page', [
            'id' => new Field\IdentifierField('id'),
            'language' => new Field\IntegerField('language'),
            'title' => new Field\TextField('title', sortable: true),
            'tags' => new Field\TextField('tags', multiple: true, filterable: true),
            'content' => new Field\TextField('content', searchable: false),
        ]);
    }

    public function getDocumentIndex(): Index
    {
        return new Index('document', [
            'id' => new Field\IdentifierField('id'),
            'language' => new Field\IntegerField('language'),
            'title' => new Field\TextField('title', sortable: true),
            'tags' => new Field\TextField('tags', multiple: true, filterable: true),
            'content' => new Field\TextField('content', searchable: false),
            'extension' => new Field\TextField('extension', searchable: false),
        ]);
    }
}
