<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Filter;

use Lochmueller\Seal\Filter\RadiusConfigurationParser;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

/**
 * Feature: geo-distance-filter, Property 1: Round-Trip des RadiusConfigurationParser
 */
class RadiusConfigurationParserPropertyTest extends AbstractTest
{
    private RadiusConfigurationParser $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new RadiusConfigurationParser();
    }

    /**
     * Feature: geo-distance-filter, Property 1: Round-Trip des RadiusConfigurationParser
     *
     * For any valid radius configuration (multi-line text with numeric values and labels),
     * parsing, formatting, and re-parsing shall produce an equivalent result:
     * parse(format(parse(config))) == parse(config)
     */
    public function testParseFormatParseRoundTrip(): void
    {
        for ($i = 0; $i < self::TEST_ITERATIONS; $i++) {
            $configuration = $this->generateRandomRadiusConfiguration();
            $firstParse = $this->subject->parse($configuration);
            $formatted = $this->subject->format($firstParse);
            $secondParse = $this->subject->parse($formatted);

            self::assertSame(
                $firstParse,
                $secondParse,
                'parse(format(parse(config))) must equal parse(config) (iteration ' . $i . ')'
                . "\nOriginal config: " . json_encode($configuration)
                . "\nFirst parse: " . json_encode($firstParse)
                . "\nFormatted: " . json_encode($formatted)
                . "\nSecond parse: " . json_encode($secondParse)
            );
        }
    }

    /**
     * Feature: geo-distance-filter, Property 1 (sub-property): Round-trip from structured array
     *
     * For any arbitrary array of radius entries with positive integer values and non-empty labels,
     * parse(format(radii)) shall return an equivalent array.
     */
    public function testFormatParseRoundTripFromArray(): void
    {
        for ($i = 0; $i < self::TEST_ITERATIONS; $i++) {
            $radii = $this->generateRandomRadiusArray();
            $formatted = $this->subject->format($radii);
            $reparsed = $this->subject->parse($formatted);

            self::assertSame(
                $radii,
                $reparsed,
                'parse(format(radii)) must equal original radii (iteration ' . $i . ')'
                . "\nOriginal radii: " . json_encode($radii)
                . "\nFormatted: " . json_encode($formatted)
                . "\nReparsed: " . json_encode($reparsed)
            );
        }
    }

    /**
     * Feature: geo-distance-filter, Property 1 (sub-property): Round-trip with empty configuration
     */
    public function testRoundTripWithEmptyConfiguration(): void
    {
        for ($i = 0; $i < self::TEST_ITERATIONS; $i++) {
            $configuration = $this->generateEmptyConfiguration();
            $firstParse = $this->subject->parse($configuration);
            $formatted = $this->subject->format($firstParse);
            $secondParse = $this->subject->parse($formatted);

            self::assertSame([], $firstParse, 'Empty configuration must parse to empty array (iteration ' . $i . ')');
            self::assertSame(
                $firstParse,
                $secondParse,
                'Round-trip of empty configuration must be stable (iteration ' . $i . ')'
            );
        }
    }

    /**
     * Feature: geo-distance-filter, Property 1 (sub-property): Round-trip with mixed valid/invalid lines
     *
     * Configurations containing a mix of valid numeric entries, invalid non-numeric entries,
     * and blank lines shall produce a stable round-trip: invalid lines are dropped on first parse,
     * and the result is stable through format and re-parse.
     */
    public function testRoundTripWithMixedValidAndInvalidLines(): void
    {
        for ($i = 0; $i < self::TEST_ITERATIONS; $i++) {
            $configuration = $this->generateMixedConfiguration();
            $firstParse = $this->subject->parse($configuration);
            $formatted = $this->subject->format($firstParse);
            $secondParse = $this->subject->parse($formatted);

            self::assertSame(
                $firstParse,
                $secondParse,
                'Round-trip with mixed valid/invalid lines must be stable (iteration ' . $i . ')'
                . "\nOriginal config: " . json_encode($configuration)
                . "\nFirst parse: " . json_encode($firstParse)
                . "\nFormatted: " . json_encode($formatted)
                . "\nSecond parse: " . json_encode($secondParse)
            );

            // Additional property: all parsed entries have integer values and non-empty labels
            foreach ($firstParse as $index => $entry) {
                self::assertIsInt($entry['value'], 'Value must be int (iteration ' . $i . ', entry ' . $index . ')');
                self::assertIsString($entry['label'], 'Label must be string (iteration ' . $i . ', entry ' . $index . ')');
            }
        }
    }

    /**
     * Generates a random array of valid radius entries for round-trip testing.
     *
     * Constraints:
     * - value: positive integer (to survive int cast round-trip)
     * - label: non-empty, trimmed, no newlines
     * - When value equals label (as string), format omits the `=` sign
     *
     * @return array<int, array{value: int, label: string}>
     */
    private function generateRandomRadiusArray(): array
    {
        $count = random_int(0, 10);
        $radii = [];

        for ($i = 0; $i < $count; $i++) {
            $value = random_int(1, 500);
            $useSameValueAndLabel = random_int(0, 2) === 0;

            if ($useSameValueAndLabel) {
                $label = (string) $value;
            } else {
                $label = $this->randomLabel();
            }

            $radii[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $radii;
    }

    /**
     * Generates a random radius configuration string with valid numeric entries,
     * blank lines, and whitespace variations.
     */
    private function generateRandomRadiusConfiguration(): string
    {
        $lineCount = random_int(0, 15);
        $lines = [];

        for ($i = 0; $i < $lineCount; $i++) {
            $lineType = random_int(0, 4);
            $lines[] = match ($lineType) {
                0 => '', // blank line
                1 => str_repeat(' ', random_int(1, 5)), // whitespace-only line
                2 => $this->randomWhitespace() . (string) random_int(1, 500) . $this->randomWhitespace(), // numeric entry without =
                3 => $this->randomWhitespace() . (string) random_int(1, 500) . $this->randomWhitespace()
                    . '=' . $this->randomWhitespace() . $this->randomLabel() . $this->randomWhitespace(), // numeric entry with =
                4 => $this->randomWhitespace() . $this->randomNonNumericWord() . $this->randomWhitespace(), // non-numeric entry (will be skipped)
            };
        }

        $separator = random_int(0, 1) === 0 ? "\n" : "\r\n";

        return implode($separator, $lines);
    }

    /**
     * Generates a configuration string containing a mix of valid numeric entries,
     * invalid non-numeric entries, and blank lines.
     */
    private function generateMixedConfiguration(): string
    {
        $lineCount = random_int(1, 15);
        $lines = [];

        for ($i = 0; $i < $lineCount; $i++) {
            $lineType = random_int(0, 5);
            $lines[] = match ($lineType) {
                0 => '', // blank line
                1 => str_repeat(' ', random_int(1, 5)), // whitespace-only
                2 => (string) random_int(1, 500) . '=' . $this->randomLabel(), // valid entry with label
                3 => (string) random_int(1, 500), // valid entry without label
                4 => $this->randomNonNumericWord() . '=' . $this->randomLabel(), // invalid: non-numeric value
                5 => $this->randomNonNumericWord(), // invalid: non-numeric without =
            };
        }

        return implode("\n", $lines);
    }

    /**
     * Generates a configuration string that is empty or contains only whitespace/blank lines.
     */
    private function generateEmptyConfiguration(): string
    {
        $type = random_int(0, 3);

        return match ($type) {
            0 => '',
            1 => str_repeat(' ', random_int(1, 10)),
            2 => str_repeat("\n", random_int(1, 5)),
            3 => implode("\n", array_map(
                static fn(): string => str_repeat(' ', random_int(0, 5)),
                range(1, random_int(1, 5))
            )),
        };
    }

    /**
     * Generates a random label string (non-empty, trimmed, no newlines, no leading/trailing whitespace).
     * Labels must not be purely numeric to avoid ambiguity with value-only format.
     */
    private function randomLabel(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_ ';
        $length = random_int(2, 20);
        $word = '';

        for ($i = 0; $i < $length; $i++) {
            $word .= $chars[random_int(0, strlen($chars) - 1)];
        }

        $word = trim($word);

        return $word !== '' ? $word : 'label';
    }

    /**
     * Generates a random non-numeric word (guaranteed to fail is_numeric check).
     */
    private function randomNonNumericWord(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz';
        $length = random_int(1, 10);
        $word = '';

        for ($i = 0; $i < $length; $i++) {
            $word .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $word;
    }

    private function randomWhitespace(): string
    {
        $count = random_int(0, 4);

        return str_repeat(' ', $count);
    }
}
