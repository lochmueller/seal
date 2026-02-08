<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Schema;

use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use Lochmueller\Seal\Event\BuildSchemaEvent;
use Lochmueller\Seal\Schema\SchemaBuilder;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use Psr\EventDispatcher\EventDispatcherInterface;

class SchemaBuilderTest extends AbstractTest
{
    private SchemaBuilder $subject;

    private EventDispatcherInterface $eventDispatcherStub;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventDispatcherStub = $this->createStub(EventDispatcherInterface::class);
        $this->eventDispatcherStub->method('dispatch')->willReturnArgument(0);

        $this->subject = new SchemaBuilder($this->eventDispatcherStub);
    }

    public function testDefaultIndexConstant(): void
    {
        self::assertSame('default', SchemaBuilder::DEFAULT_INDEX);
    }

    public function testGetSchemaReturnsSchemaWithDefaultIndex(): void
    {
        $schema = $this->subject->getSchema();

        self::assertInstanceOf(Schema::class, $schema);
        self::assertArrayHasKey(SchemaBuilder::DEFAULT_INDEX, $schema->indexes);
    }

    public function testGetSchemaDispatchesBuildSchemaEvent(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(BuildSchemaEvent::class))
            ->willReturnArgument(0);

        $subject = new SchemaBuilder($eventDispatcher);
        $subject->getSchema();
    }

    public function testGetSchemaReturnsEventModifiedSchema(): void
    {
        $modifiedSchema = new Schema([
            'custom' => new Index('custom', [
                'id' => new Field\IdentifierField('id'),
            ]),
        ]);

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')
            ->willReturnCallback(function (BuildSchemaEvent $event) use ($modifiedSchema): BuildSchemaEvent {
                $event->schema = $modifiedSchema;
                return $event;
            });

        $subject = new SchemaBuilder($eventDispatcher);
        $schema = $subject->getSchema();

        self::assertSame($modifiedSchema, $schema);
        self::assertArrayHasKey('custom', $schema->indexes);
        self::assertArrayNotHasKey(SchemaBuilder::DEFAULT_INDEX, $schema->indexes);
    }

    public function testGetPageIndexReturnsIndex(): void
    {
        $index = $this->subject->getPageIndex();

        self::assertInstanceOf(Index::class, $index);
        self::assertSame(SchemaBuilder::DEFAULT_INDEX, $index->name);
    }

    public function testGetPageIndexContainsExpectedFields(): void
    {
        $index = $this->subject->getPageIndex();

        $expectedFields = [
            'id', 'site', 'language', 'uri', 'location', 'indexdate',
            'title', 'content', 'tags', 'size', 'extension', 'preview',
        ];

        foreach ($expectedFields as $fieldName) {
            self::assertArrayHasKey($fieldName, $index->fields, "Field '{$fieldName}' missing from page index");
        }
    }

    public function testGetPageIndexFieldTypes(): void
    {
        $index = $this->subject->getPageIndex();

        self::assertInstanceOf(Field\IdentifierField::class, $index->fields['id']);
        self::assertInstanceOf(Field\TextField::class, $index->fields['site']);
        self::assertInstanceOf(Field\TextField::class, $index->fields['language']);
        self::assertInstanceOf(Field\TextField::class, $index->fields['uri']);
        self::assertInstanceOf(Field\DateTimeField::class, $index->fields['indexdate']);
        self::assertInstanceOf(Field\TextField::class, $index->fields['title']);
        self::assertInstanceOf(Field\TextField::class, $index->fields['content']);
        self::assertInstanceOf(Field\TextField::class, $index->fields['tags']);
        self::assertInstanceOf(Field\IntegerField::class, $index->fields['size']);
        self::assertInstanceOf(Field\TextField::class, $index->fields['extension']);
        self::assertInstanceOf(Field\TextField::class, $index->fields['preview']);
    }

    public function testGetPageIndexContainsLocationFieldOfTypeGeoPointField(): void
    {
        $index = $this->subject->getPageIndex();

        self::assertArrayHasKey('location', $index->fields, "Field 'location' missing from page index");
        self::assertInstanceOf(Field\GeoPointField::class, $index->fields['location']);
    }

    public function testGetPageIndexLocationFieldIsFilterable(): void
    {
        $index = $this->subject->getPageIndex();

        self::assertTrue($index->fields['location']->filterable, "Field 'location' should be filterable");
    }

}
