<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Filter;

use Lochmueller\Seal\Filter\RadiusConfigurationParser;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

class RadiusConfigurationParserTest extends AbstractTest
{
    private RadiusConfigurationParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new RadiusConfigurationParser();
    }

    public function testParseEmptyStringReturnsEmptyArray(): void
    {
        $result = $this->parser->parse('');

        self::assertSame([], $result);
    }

    public function testParseOnlyWhitespaceLinesReturnsEmptyArray(): void
    {
        $configuration = "\n\n   \n\t\n";

        $result = $this->parser->parse($configuration);

        self::assertSame([], $result);
    }

    public function testParseSkipsInvalidLines(): void
    {
        $configuration = "10=10 km\nabc=Invalid\n50=50 km";

        $result = $this->parser->parse($configuration);

        self::assertCount(2, $result);
        self::assertSame(['value' => 10, 'label' => '10 km'], $result[0]);
        self::assertSame(['value' => 50, 'label' => '50 km'], $result[1]);
    }

    public function testParseSkipsBlankLinesBetweenValidEntries(): void
    {
        $configuration = "10=10 km\n\n25=25 km\n   \n50=50 km\n";

        $result = $this->parser->parse($configuration);

        self::assertCount(3, $result);
        self::assertSame(['value' => 10, 'label' => '10 km'], $result[0]);
        self::assertSame(['value' => 25, 'label' => '25 km'], $result[1]);
        self::assertSame(['value' => 50, 'label' => '50 km'], $result[2]);
    }

    /**
     * Teste Zeilen ohne `=` verwenden Wert als Label.
     */
    public function testParseLinesWithoutEqualsUseValueAsLabel(): void
    {
        $configuration = "10\n25\n50";

        $result = $this->parser->parse($configuration);

        self::assertCount(3, $result);
        self::assertSame(['value' => 10, 'label' => '10'], $result[0]);
        self::assertSame(['value' => 25, 'label' => '25'], $result[1]);
        self::assertSame(['value' => 50, 'label' => '50'], $result[2]);
    }

    /**
     * Teste nicht-numerische Werte werden übersprungen.
     */
    public function testParseSkipsNonNumericValues(): void
    {
        $configuration = "abc\nxyz=Some Label\n10=10 km\nfoo";

        $result = $this->parser->parse($configuration);

        self::assertCount(1, $result);
        self::assertSame(['value' => 10, 'label' => '10 km'], $result[0]);
    }

    public function testParseSkipsLinesWithEmptyValueBeforeEquals(): void
    {
        $configuration = "=Label\n10=10 km";

        $result = $this->parser->parse($configuration);

        self::assertCount(1, $result);
        self::assertSame(['value' => 10, 'label' => '10 km'], $result[0]);
    }

    /**
     * Teste gemischte Zeilen mit und ohne `=`.
     */
    public function testParseMixedEntriesWithAndWithoutEquals(): void
    {
        $configuration = "10=10 km\n25\n50=50 km";

        $result = $this->parser->parse($configuration);

        self::assertCount(3, $result);
        self::assertSame(['value' => 10, 'label' => '10 km'], $result[0]);
        self::assertSame(['value' => 25, 'label' => '25'], $result[1]);
        self::assertSame(['value' => 50, 'label' => '50 km'], $result[2]);
    }
}
