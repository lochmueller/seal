<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Filter;

use Lochmueller\Seal\Filter\TagConfigurationParser;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

/**
 * Feature: tag-faceting, Property 1: Parsen erzeugt korrekte Struktur mit getrimmten Werten
 *
 * Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5
 */
class TagConfigurationParserPropertyTest extends AbstractTest
{
    private const PROPERTY_TEST_ITERATIONS = 100;

    private TagConfigurationParser $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new TagConfigurationParser();
    }

    /**
     * Feature: tag-faceting, Property 1: Parsen erzeugt korrekte Struktur mit getrimmten Werten
     *
     * For any arbitrary tag configuration string (including blank lines, entries with and without `=`,
     * arbitrary whitespace padding), parsing shall produce an array where each entry contains the keys
     * `value` and `label`, both without leading or trailing whitespace, and blank lines are ignored.
     *
     * **Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5**
     */
    public function testParseProducesCorrectStructureWithTrimmedValues(): void
    {
        for ($i = 0; $i < self::PROPERTY_TEST_ITERATIONS; $i++) {
            $configuration = $this->generateRandomConfiguration();
            $result = $this->subject->parse($configuration);

            // Property: result is always an array
            self::assertIsArray($result, 'Parse result must be an array (iteration ' . $i . ')');

            foreach ($result as $index => $entry) {
                // Property: each entry has exactly the keys 'value' and 'label'
                self::assertArrayHasKey('value', $entry, 'Entry ' . $index . ' must have key "value" (iteration ' . $i . ')');
                self::assertArrayHasKey('label', $entry, 'Entry ' . $index . ' must have key "label" (iteration ' . $i . ')');
                self::assertCount(2, $entry, 'Entry ' . $index . ' must have exactly 2 keys (iteration ' . $i . ')');

                // Property: value and label are strings
                self::assertIsString($entry['value'], 'Value must be a string (iteration ' . $i . ', entry ' . $index . ')');
                self::assertIsString($entry['label'], 'Label must be a string (iteration ' . $i . ', entry ' . $index . ')');

                // Property: value and label are trimmed (no leading/trailing whitespace)
                self::assertSame($entry['value'], trim($entry['value']), 'Value must be trimmed (iteration ' . $i . ', entry ' . $index . ')');
                self::assertSame($entry['label'], trim($entry['label']), 'Label must be trimmed (iteration ' . $i . ', entry ' . $index . ')');

                // Property: value is never empty string (Req 1.1 - valid entries have non-empty values)
                self::assertNotSame('', $entry['value'], 'Value must not be empty string (iteration ' . $i . ', entry ' . $index . ')');
            }

            // Property: blank lines are ignored (Req 1.2)
            $this->assertBlankLinesIgnored($configuration, $result);
        }
    }

    /**
     * Feature: tag-faceting, Property 1 (sub-property): Entries without `=` use value as label
     *
     * **Validates: Requirement 1.3**
     */
    public function testEntriesWithoutEqualsUseValueAsLabel(): void
    {
        for ($i = 0; $i < self::PROPERTY_TEST_ITERATIONS; $i++) {
            $configuration = $this->generateConfigurationWithoutEquals();
            $result = $this->subject->parse($configuration);

            foreach ($result as $index => $entry) {
                // Property: when no `=` is present, value and label are identical
                self::assertSame(
                    $entry['value'],
                    $entry['label'],
                    'Without "=", value and label must be identical (iteration ' . $i . ', entry ' . $index . ')'
                );
            }
        }
    }

    /**
     * Feature: tag-faceting, Property 1 (sub-property): Empty configuration returns empty array
     *
     * **Validates: Requirement 1.4**
     */
    public function testEmptyConfigurationReturnsEmptyArray(): void
    {
        for ($i = 0; $i < self::PROPERTY_TEST_ITERATIONS; $i++) {
            $configuration = $this->generateEmptyConfiguration();
            $result = $this->subject->parse($configuration);

            self::assertSame([], $result, 'Empty/whitespace-only configuration must return empty array (iteration ' . $i . ')');
        }
    }

    /**
     * Feature: tag-faceting, Property 2: Parse-Format Round-Trip
     *
     * For any arbitrary array of tag entries with non-empty, trimmed `value` and `label` values,
     * `parse(format(tags))` shall return an equivalent array.
     *
     * **Validates: Requirements 1.6**
     */
    public function testParseFormatRoundTrip(): void
    {
        for ($i = 0; $i < self::PROPERTY_TEST_ITERATIONS; $i++) {
            $tags = $this->generateRandomTagArray();
            $formatted = $this->subject->format($tags);
            $reparsed = $this->subject->parse($formatted);

            self::assertSame(
                $tags,
                $reparsed,
                'parse(format(tags)) must equal original tags (iteration ' . $i . ')'
                . "\nOriginal tags: " . json_encode($tags)
                . "\nFormatted: " . json_encode($formatted)
                . "\nReparsed: " . json_encode($reparsed)
            );
        }
    }

    /**
     * Feature: tag-faceting, Property 2 (sub-property): Round-trip with empty array
     *
     * **Validates: Requirements 1.6**
     */
    public function testParseFormatRoundTripWithEmptyArray(): void
    {
        $tags = [];
        $formatted = $this->subject->format($tags);
        $reparsed = $this->subject->parse($formatted);

        self::assertSame($tags, $reparsed, 'parse(format([])) must equal []');
    }

    /**
     * Generates a random array of valid tag entries for round-trip testing.
     *
     * Constraints:
     * - value: non-empty, trimmed, no `=`, no newlines
     * - label: non-empty, trimmed, no newlines
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function generateRandomTagArray(): array
    {
        $count = random_int(0, 10);
        $tags = [];

        for ($i = 0; $i < $count; $i++) {
            $useSameValueAndLabel = random_int(0, 2) === 0;
            $value = $this->randomRoundTripSafeWord(excludeEquals: true);

            if ($useSameValueAndLabel) {
                $label = $value;
            } else {
                $label = $this->randomRoundTripSafeWord(excludeEquals: false);
            }

            $tags[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $tags;
    }

    /**
     * Generates a random non-empty, trimmed word safe for round-trip testing.
     *
     * - No leading/trailing whitespace
     * - No newline characters
     * - When excludeEquals is true, no `=` character (for value field)
     */
    private function randomRoundTripSafeWord(bool $excludeEquals): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_.:;!?@#$%^&*()[]{}|<>~/+';
        if (!$excludeEquals) {
            $chars .= '=';
        }

        $length = random_int(1, 20);
        $word = '';
        for ($j = 0; $j < $length; $j++) {
            $word .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $word;
    }


    /**
     * Asserts that the number of parsed entries matches the number of non-blank lines.
     */
    private function assertBlankLinesIgnored(string $configuration, array $result): void
    {
        $lines = preg_split('/\r?\n/', $configuration);
        if ($lines === false) {
            $lines = [];
        }

        $nonBlankLines = array_filter($lines, static fn(string $line): bool => trim($line) !== '');
        // Filter out lines that would result in empty values after trimming
        $validLines = array_filter($nonBlankLines, static function (string $line): bool {
            $trimmedLine = trim($line);
            $equalsPosition = strpos($trimmedLine, '=');
            if ($equalsPosition === false) {
                return $trimmedLine !== '';
            }
            return trim(substr($trimmedLine, 0, $equalsPosition)) !== '';
        });

        self::assertCount(
            count($validLines),
            $result,
            'Number of parsed entries must match number of valid non-blank lines'
        );
    }

    /**
     * Generates a random tag configuration string with mixed entries.
     */
    private function generateRandomConfiguration(): string
    {
        $lineCount = random_int(0, 15);
        $lines = [];

        for ($i = 0; $i < $lineCount; $i++) {
            $lineType = random_int(0, 4);
            $lines[] = match ($lineType) {
                0 => '', // blank line
                1 => str_repeat(' ', random_int(1, 5)), // whitespace-only line
                2 => $this->randomWhitespace() . $this->randomWord() . $this->randomWhitespace(), // entry without =
                3 => $this->randomWhitespace() . $this->randomWord() . $this->randomWhitespace()
                    . '=' . $this->randomWhitespace() . $this->randomWord() . $this->randomWhitespace(), // entry with =
                4 => $this->randomWhitespace() . $this->randomWord() . $this->randomWhitespace()
                    . '=' . $this->randomWhitespace() . $this->randomWord() . '=' . $this->randomWord() . $this->randomWhitespace(), // entry with multiple =
            };
        }

        // Randomly use \n or \r\n as line separator
        $separator = random_int(0, 1) === 0 ? "\n" : "\r\n";

        return implode($separator, $lines);
    }

    /**
     * Generates a configuration string with entries that have no `=` sign.
     */
    private function generateConfigurationWithoutEquals(): string
    {
        $lineCount = random_int(1, 10);
        $lines = [];

        for ($i = 0; $i < $lineCount; $i++) {
            if (random_int(0, 3) === 0) {
                $lines[] = ''; // occasional blank line
            } else {
                $lines[] = $this->randomWhitespace() . $this->randomWordWithoutEquals() . $this->randomWhitespace();
            }
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

    private function randomWord(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';
        $length = random_int(1, 15);
        $word = '';
        for ($i = 0; $i < $length; $i++) {
            $word .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $word;
    }

    private function randomWordWithoutEquals(): string
    {
        // Same as randomWord but guaranteed to not contain '='
        return $this->randomWord();
    }

    private function randomWhitespace(): string
    {
        $count = random_int(0, 4);

        return str_repeat(' ', $count);
    }
}
