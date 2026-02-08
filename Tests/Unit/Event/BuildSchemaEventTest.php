<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Event;

use CmsIg\Seal\Schema\Field\TextField;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use Lochmueller\Seal\Event\BuildSchemaEvent;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

class BuildSchemaEventTest extends AbstractTest
{
    public function testConstructorSetsSchema(): void
    {
        $schema = new Schema([
            'test' => new Index('test', [
                'title' => new TextField('title'),
            ]),
        ]);

        $event = new BuildSchemaEvent($schema);

        self::assertSame($schema, $event->schema);
    }

    public function testSchemaIsModifiable(): void
    {
        $originalSchema = new Schema([
            'test' => new Index('test', [
                'title' => new TextField('title'),
            ]),
        ]);

        $event = new BuildSchemaEvent($originalSchema);

        $newSchema = new Schema([
            'other' => new Index('other', [
                'content' => new TextField('content'),
            ]),
        ]);

        $event->schema = $newSchema;

        self::assertSame($newSchema, $event->schema);
        self::assertNotSame($originalSchema, $event->schema);
    }
}
