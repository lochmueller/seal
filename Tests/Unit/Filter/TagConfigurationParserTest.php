<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Filter;

use Lochmueller\Seal\Filter\TagConfigurationParser;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

class TagConfigurationParserTest extends AbstractTest
{
    private TagConfigurationParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new TagConfigurationParser();
    }

    public function testParseSimpleConfiguration(): void
    {
        $configuration = "page=Seiten\nfile=Dateien\nnews=Nachrichten";

        $result = $this->parser->parse($configuration);

        self::assertCount(3, $result);
        self::assertSame(['value' => 'page', 'label' => 'Seiten'], $result[0]);
        self::assertSame(['value' => 'file', 'label' => 'Dateien'], $result[1]);
        self::assertSame(['value' => 'news', 'label' => 'Nachrichten'], $result[2]);
    }

    public function testParseEmptyStringReturnsEmptyArray(): void
    {
        $result = $this->parser->parse('');

        self::assertSame([], $result);
    }

    public function testParseOnlyBlankLinesReturnsEmptyArray(): void
    {
        $configuration = "\n\n   \n\t\n";

        $result = $this->parser->parse($configuration);

        self::assertSame([], $result);
    }

    public function testParseEntriesWithoutEqualsUseValueAsLabel(): void
    {
        $configuration = "page\nfile\nnews";

        $result = $this->parser->parse($configuration);

        self::assertCount(3, $result);
        self::assertSame(['value' => 'page', 'label' => 'page'], $result[0]);
        self::assertSame(['value' => 'file', 'label' => 'file'], $result[1]);
        self::assertSame(['value' => 'news', 'label' => 'news'], $result[2]);
    }

    public function testParseTrimsWhitespaceFromValueAndLabel(): void
    {
        $configuration = "  page  =  Seiten  \n  file  =  Dateien  ";

        $result = $this->parser->parse($configuration);

        self::assertCount(2, $result);
        self::assertSame(['value' => 'page', 'label' => 'Seiten'], $result[0]);
        self::assertSame(['value' => 'file', 'label' => 'Dateien'], $result[1]);
    }

    public function testParseSplitsAtFirstEqualsOnly(): void
    {
        $configuration = "key=value=with=equals";

        $result = $this->parser->parse($configuration);

        self::assertCount(1, $result);
        self::assertSame(['value' => 'key', 'label' => 'value=with=equals'], $result[0]);
    }

    public function testParseMixedValidEntriesAndBlankLines(): void
    {
        $configuration = "page=Seiten\n\nfile=Dateien\n   \nnews=Nachrichten\n";

        $result = $this->parser->parse($configuration);

        self::assertCount(3, $result);
        self::assertSame(['value' => 'page', 'label' => 'Seiten'], $result[0]);
        self::assertSame(['value' => 'file', 'label' => 'Dateien'], $result[1]);
        self::assertSame(['value' => 'news', 'label' => 'Nachrichten'], $result[2]);
    }

    public function testParseWindowsStyleLineEndings(): void
    {
        $configuration = "page=Seiten\r\nfile=Dateien\r\nnews=Nachrichten";

        $result = $this->parser->parse($configuration);

        self::assertCount(3, $result);
        self::assertSame(['value' => 'page', 'label' => 'Seiten'], $result[0]);
        self::assertSame(['value' => 'file', 'label' => 'Dateien'], $result[1]);
        self::assertSame(['value' => 'news', 'label' => 'Nachrichten'], $result[2]);
    }

    public function testParseMixedEntriesWithAndWithoutEquals(): void
    {
        $configuration = "page=Seiten\nsimple\nfile=Dateien";

        $result = $this->parser->parse($configuration);

        self::assertCount(3, $result);
        self::assertSame(['value' => 'page', 'label' => 'Seiten'], $result[0]);
        self::assertSame(['value' => 'simple', 'label' => 'simple'], $result[1]);
        self::assertSame(['value' => 'file', 'label' => 'Dateien'], $result[2]);
    }

    public function testParseEntryWithEmptyValueAfterTrimmingIsIgnored(): void
    {
        $configuration = "  =Label\npage=Seiten";

        $result = $this->parser->parse($configuration);

        self::assertCount(1, $result);
        self::assertSame(['value' => 'page', 'label' => 'Seiten'], $result[0]);
    }

    public function testParseEntryWithEmptyLabelKeepsEmptyLabel(): void
    {
        $configuration = "page=";

        $result = $this->parser->parse($configuration);

        self::assertCount(1, $result);
        self::assertSame(['value' => 'page', 'label' => ''], $result[0]);
    }

    public function testFormatSimpleConfiguration(): void
    {
        $tags = [
            ['value' => 'page', 'label' => 'Seiten'],
            ['value' => 'file', 'label' => 'Dateien'],
        ];

        $result = $this->parser->format($tags);

        self::assertSame("page=Seiten\nfile=Dateien", $result);
    }

    public function testFormatWhenValueEqualsLabelOmitsEquals(): void
    {
        $tags = [
            ['value' => 'page', 'label' => 'page'],
            ['value' => 'file', 'label' => 'Dateien'],
        ];

        $result = $this->parser->format($tags);

        self::assertSame("page\nfile=Dateien", $result);
    }

    public function testFormatEmptyArrayReturnsEmptyString(): void
    {
        $result = $this->parser->format([]);

        self::assertSame('', $result);
    }
}
