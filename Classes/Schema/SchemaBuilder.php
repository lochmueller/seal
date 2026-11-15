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
    public const DEFAULT_INDEX = 'default';
    public const DEFAULT_SCHEMA = 'default';

    public function __construct(protected EventDispatcherInterface $eventDispatcher) {}

    /**
     * @param array<string, array{
     *     source: string,
     *     type: string,
     *     multiple: bool,
     *     searchable: bool,
     *     filterable: bool,
     *     sortable: bool,
     *     distinct: bool,
     *     facet: bool,
     *     options: array<string, mixed>
     * }> $recordFieldDefinitions
     */
    public function getSchema(array $recordFieldDefinitions = []): Schema
    {
        $pageIndex = $this->getPageIndex();
        $fields = $pageIndex->fields;
        foreach ($recordFieldDefinitions as $name => $definition) {
            if (isset($fields[$name]) && !in_array($name, ['title', 'content'], true)) {
                throw new \InvalidArgumentException('Record field "' . $name . '" conflicts with a reserved SEAL field.');
            }
            $fields[$name] = $this->createRecordField($name, $definition);
        }

        $schema = new Schema([
            self::DEFAULT_SCHEMA => new Index(self::DEFAULT_INDEX, $fields),
        ]);

        $event = new BuildSchemaEvent($schema);
        $this->eventDispatcher->dispatch($event);
        return $event->schema;
    }

    /**
     * @param array{
     *     source: string,
     *     type: string,
     *     multiple: bool,
     *     searchable: bool,
     *     filterable: bool,
     *     sortable: bool,
     *     distinct: bool,
     *     facet: bool,
     *     options: array<string, mixed>
     * } $definition
     */
    private function createRecordField(string $name, array $definition): Field\AbstractField
    {
        $options = [
            'multiple' => $definition['multiple'],
            'searchable' => $definition['searchable'],
            'filterable' => $definition['filterable'],
            'sortable' => $definition['sortable'],
            'distinct' => $definition['distinct'],
            'facet' => $definition['facet'],
            'options' => $definition['options'],
        ];
        if ($definition['type'] === 'text' && $name === 'title') {
            $options['searchable'] = true;
            $options['sortable'] = true;
        } elseif ($definition['type'] === 'text' && $name === 'content') {
            $options['searchable'] = true;
        }

        return match ($definition['type']) {
            'text' => new Field\TextField($name, ...$options),
            'integer' => new Field\IntegerField(
                $name,
                $options['multiple'],
                false,
                $options['filterable'],
                $options['sortable'],
                $options['distinct'],
                $options['facet'],
                $options['options'],
            ),
            'float' => new Field\FloatField(
                $name,
                $options['multiple'],
                false,
                $options['filterable'],
                $options['sortable'],
                $options['distinct'],
                $options['facet'],
                $options['options'],
            ),
            'boolean' => new Field\BooleanField(
                $name,
                $options['multiple'],
                false,
                $options['filterable'],
                $options['sortable'],
                $options['distinct'],
                $options['facet'],
                $options['options'],
            ),
            'datetime' => new Field\DateTimeField(
                $name,
                $options['multiple'],
                false,
                $options['filterable'],
                $options['sortable'],
                $options['distinct'],
                $options['facet'],
                $options['options'],
            ),
            default => throw new \InvalidArgumentException('Unsupported SEAL record field type: ' . $definition['type']),
        };
    }

    public function getPageIndex(): Index
    {
        return new Index(SchemaBuilder::DEFAULT_INDEX, [
            'id' => new Field\IdentifierField('id'), // Page ID or PageID incl. suffix and record ID. Example: 128 or 291-tx_news-12839
            // Meta
            'site' => new Field\TextField('site', searchable: false, filterable: true), // Site identifier. Example: portal
            'language' => new Field\TextField('language', searchable: false, filterable: true), // Language UID as string. Example: "0"
            'uri' => new Field\TextField('uri', searchable: false), // URI.
            'record_table' => new Field\TextField('record_table', searchable: false, filterable: true), // TCA table for record documents.
            'record_uid' => new Field\IntegerField('record_uid', filterable: true), // Record UID for record documents.
            'location' => new Field\GeoPointField('location', filterable: true), // Geo coordinates for distance search
            'indexdate' => new Field\DateTimeField('indexdate'), // The date time of the index insert/update
            // Content
            'title' => new Field\TextField('title', sortable: true), // Title. Example: Homepage - My Company
            'content' => new Field\TextField('content'), // Content. Example: I am a long string example
            'tags' => new Field\TextField('tags', multiple: true, filterable: true, facet: true), // Tags. Defaults are "Page" "File"
            // File
            'size' => new Field\IntegerField('size', searchable: false), // File size in byte in case of file. Otherwise 0. Example: 123789
            'extension' => new Field\TextField('extension'), // File extension in case of file. Otherwise empty. Example: html, pdf, png
            'preview' => new Field\TextField('preview', searchable: false), // Media preview.
        ]);
    }
}
