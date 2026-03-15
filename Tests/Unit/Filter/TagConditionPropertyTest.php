<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Filter;

use CmsIg\Seal\Search\Condition\InCondition;
use Lochmueller\Seal\Filter\TagCondition;
use Lochmueller\Seal\Filter\TagConfigurationParser;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Feature: tag-faceting, Property 3: TagCondition erzeugt Bedingungen nur für gültige ausgewählte Tags
 */
class TagConditionPropertyTest extends AbstractTest
{
    private TagCondition $subject;

    private TagConfigurationParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new TagConfigurationParser();
        $this->subject = new TagCondition($this->parser);
    }

    /**
     * Feature: tag-faceting, Property 3: TagCondition erzeugt Bedingungen nur für gültige ausgewählte Tags
     *
     * For any arbitrary set of configured tags and any arbitrary set of selected values
     * (including invalid values), the TagCondition shall return either an empty array (when
     * no valid values are selected) or a single InCondition containing exactly the valid
     * selected values. The condition shall target the field `tags`.
     */
    public function testOnlyValidSelectedTagsProduceConditions(): void
    {
        for ($i = 0; $i < self::TEST_ITERATIONS; $i++) {
            $configuredTags = $this->generateRandomTagEntries();
            $configurationString = $this->parser->format($configuredTags);
            $allowedValues = array_map(
                static fn(array $tag): string => $tag['value'],
                $configuredTags,
            );

            $selectedValues = $this->generateRandomSelectedValues($allowedValues);
            $uid = random_int(1, 9999);

            $filterItem = [
                'uid' => $uid,
                'tags' => $configurationString,
            ];

            $request = $this->createMockRequest($uid, $selectedValues);
            $conditions = $this->subject->getFilterConfiguration($filterItem, $request);

            // Compute expected valid values (intersection of selected and allowed)
            $expectedValidValues = array_values(array_intersect($selectedValues, $allowedValues));

            if ($expectedValidValues === []) {
                // Property: no valid values means empty conditions
                self::assertSame(
                    [],
                    $conditions,
                    'When no valid tags are selected, conditions must be empty (iteration ' . $i . ')',
                );
            } else {
                // Property: exactly one InCondition with all valid values
                self::assertCount(
                    1,
                    $conditions,
                    'Must return exactly one InCondition (iteration ' . $i . ')'
                    . "\nConfigured tags: " . json_encode($allowedValues)
                    . "\nSelected values: " . json_encode($selectedValues)
                    . "\nExpected valid: " . json_encode($expectedValidValues),
                );

                self::assertInstanceOf(
                    InCondition::class,
                    $conditions[0],
                    'Condition must be an InCondition (iteration ' . $i . ')',
                );

                self::assertSame(
                    'tags',
                    $conditions[0]->field,
                    'Condition must target field "tags" (iteration ' . $i . ')',
                );

                // Property: the InCondition values contain exactly the valid selected values
                self::assertSame(
                    $expectedValidValues,
                    array_values($conditions[0]->values),
                    'InCondition values must match valid selected tags (iteration ' . $i . ')'
                    . "\nCondition values: " . json_encode($conditions[0]->values)
                    . "\nExpected valid: " . json_encode($expectedValidValues),
                );
            }
        }
    }

    /**
     * Feature: tag-faceting, Property 3 (sub-property): No tags selected returns empty array
     */
    public function testNoTagsSelectedReturnsEmptyArray(): void
    {
        for ($i = 0; $i < self::TEST_ITERATIONS; $i++) {
            $configuredTags = $this->generateRandomTagEntries();
            $configurationString = $this->parser->format($configuredTags);
            $uid = random_int(1, 9999);

            $filterItem = [
                'uid' => $uid,
                'tags' => $configurationString,
            ];

            // No tags selected: empty array
            $request = $this->createMockRequest($uid, []);
            $conditions = $this->subject->getFilterConfiguration($filterItem, $request);

            self::assertSame(
                [],
                $conditions,
                'When no tags are selected, conditions must be empty (iteration ' . $i . ')',
            );
        }
    }

    /**
     * Feature: tag-faceting, Property 3 (sub-property): Only invalid tags selected returns empty array
     */
    public function testOnlyInvalidTagsSelectedReturnsEmptyArray(): void
    {
        for ($i = 0; $i < self::TEST_ITERATIONS; $i++) {
            $configuredTags = $this->generateRandomTagEntries();
            $configurationString = $this->parser->format($configuredTags);
            $allowedValues = array_map(
                static fn(array $tag): string => $tag['value'],
                $configuredTags,
            );

            // Generate only values that are NOT in the configured tags
            $invalidValues = $this->generateOnlyInvalidValues($allowedValues);
            $uid = random_int(1, 9999);

            $filterItem = [
                'uid' => $uid,
                'tags' => $configurationString,
            ];

            $request = $this->createMockRequest($uid, $invalidValues);
            $conditions = $this->subject->getFilterConfiguration($filterItem, $request);

            self::assertSame(
                [],
                $conditions,
                'When only invalid tags are selected, conditions must be empty (iteration ' . $i . ')'
                . "\nConfigured tags: " . json_encode($allowedValues)
                . "\nInvalid selected: " . json_encode($invalidValues),
            );
        }
    }

    /**
     * Creates a mock ServerRequestInterface that returns the given selected values
     * via getParsedBody()['tx_seal_search']['field_{uid}'].
     *
     * @param int $uid
     * @param array<int, string> $selectedValues
     */
    private function createMockRequest(int $uid, array $selectedValues): ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([
            'tx_seal_search' => [
                'field_' . $uid => $selectedValues,
            ],
        ]);

        return $request;
    }

    /**
     * Generates a random array of tag entries with non-empty, trimmed values.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function generateRandomTagEntries(): array
    {
        $count = random_int(0, 8);
        $tags = [];
        $usedValues = [];

        for ($i = 0; $i < $count; $i++) {
            $value = $this->randomTagValue();
            // Ensure unique values to avoid ambiguity
            if (in_array($value, $usedValues, true)) {
                continue;
            }
            $usedValues[] = $value;

            $tags[] = [
                'value' => $value,
                'label' => $this->randomLabel(),
            ];
        }

        return $tags;
    }

    /**
     * Generates a random set of selected values, mixing valid (from allowed) and invalid values.
     *
     * @param array<int, string> $allowedValues
     * @return array<int, string>
     */
    private function generateRandomSelectedValues(array $allowedValues): array
    {
        $selected = [];

        // Randomly pick some valid values
        if ($allowedValues !== []) {
            $validCount = random_int(0, count($allowedValues));
            $shuffled = $allowedValues;
            shuffle($shuffled);
            for ($i = 0; $i < $validCount; $i++) {
                $selected[] = $shuffled[$i];
            }
        }

        // Randomly add some invalid values
        $invalidCount = random_int(0, 5);
        for ($i = 0; $i < $invalidCount; $i++) {
            $invalid = $this->randomInvalidValue($allowedValues);
            $selected[] = $invalid;
        }

        shuffle($selected);

        return $selected;
    }

    /**
     * Generates an array containing only values NOT in the allowed set.
     *
     * @param array<int, string> $allowedValues
     * @return array<int, string>
     */
    private function generateOnlyInvalidValues(array $allowedValues): array
    {
        $count = random_int(1, 5);
        $values = [];

        for ($i = 0; $i < $count; $i++) {
            $values[] = $this->randomInvalidValue($allowedValues);
        }

        return $values;
    }

    /**
     * Generates a random value guaranteed to NOT be in the allowed values.
     *
     * @param array<int, string> $allowedValues
     */
    private function randomInvalidValue(array $allowedValues): string
    {
        // Use a prefix that makes collision with real tag values extremely unlikely
        $value = '__invalid_' . random_int(10000, 99999) . '_' . $this->randomTagValue();

        // Double-check it's not in allowed values (extremely unlikely but safe)
        while (in_array($value, $allowedValues, true)) {
            $value = '__invalid_' . random_int(10000, 99999) . '_' . $this->randomTagValue();
        }

        return $value;
    }

    /**
     * Generates a random tag value (non-empty, trimmed, no `=`, no newlines).
     */
    private function randomTagValue(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';
        $length = random_int(1, 15);
        $word = '';

        for ($i = 0; $i < $length; $i++) {
            $word .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $word;
    }

    /**
     * Generates a random label string (non-empty, trimmed, no newlines).
     */
    private function randomLabel(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_ ÄÖÜäöüß';
        $length = random_int(1, 20);
        $word = '';

        for ($i = 0; $i < $length; $i++) {
            $index = random_int(0, mb_strlen($chars) - 1);
            $word .= mb_substr($chars, $index, 1);
        }

        return trim($word) ?: 'label';
    }
}
