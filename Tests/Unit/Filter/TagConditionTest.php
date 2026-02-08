<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Filter;

use CmsIg\Seal\Search\Condition\EqualCondition;
use Lochmueller\Seal\Filter\TagCondition;
use Lochmueller\Seal\Filter\TagConfigurationParser;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Unit tests for TagCondition with concrete scenarios.
 *
 * Validates: Requirements 2.1, 2.2, 2.3
 */
class TagConditionTest extends AbstractTest
{
    private TagCondition $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new TagCondition(new TagConfigurationParser());
    }

    public function testGetTypeReturnsTagCondition(): void
    {
        self::assertSame('tagCondition', $this->subject->getType());
    }

    /**
     * Validates: Requirement 2.2
     */
    public function testNoTagsSelectedReturnsEmptyConditions(): void
    {
        $filterItem = [
            'uid' => 123,
            'tags' => "page=Seiten\nfile=Dateien\nnews=Nachrichten",
        ];

        $request = $this->createStubServerRequest(123, []);
        $conditions = $this->subject->getFilterConfiguration($filterItem, $request);

        self::assertSame([], $conditions);
    }

    /**
     * Validates: Requirement 2.1
     */
    public function testValidTagsSelectedProduceEqualConditions(): void
    {
        $filterItem = [
            'uid' => 42,
            'tags' => "page=Seiten\nfile=Dateien\nnews=Nachrichten",
        ];

        $request = $this->createStubServerRequest(42, ['page', 'file']);
        $conditions = $this->subject->getFilterConfiguration($filterItem, $request);

        self::assertCount(2, $conditions);

        self::assertInstanceOf(EqualCondition::class, $conditions[0]);
        self::assertSame('tags', $conditions[0]->field);
        self::assertSame('page', $conditions[0]->value);

        self::assertInstanceOf(EqualCondition::class, $conditions[1]);
        self::assertSame('tags', $conditions[1]->field);
        self::assertSame('file', $conditions[1]->value);
    }

    /**
     * Validates: Requirement 2.3
     */
    public function testInvalidTagsSelectedReturnsEmptyConditions(): void
    {
        $filterItem = [
            'uid' => 99,
            'tags' => "page=Seiten\nfile=Dateien",
        ];

        $request = $this->createStubServerRequest(99, ['unknown', 'invalid']);
        $conditions = $this->subject->getFilterConfiguration($filterItem, $request);

        self::assertSame([], $conditions);
    }

    /**
     * Validates: Requirements 2.1, 2.3
     */
    public function testMixedValidAndInvalidTagsOnlyProduceValidConditions(): void
    {
        $filterItem = [
            'uid' => 55,
            'tags' => "page=Seiten\nfile=Dateien\nnews=Nachrichten",
        ];

        $request = $this->createStubServerRequest(55, ['page', 'invalid', 'news', 'unknown']);
        $conditions = $this->subject->getFilterConfiguration($filterItem, $request);

        self::assertCount(2, $conditions);

        self::assertInstanceOf(EqualCondition::class, $conditions[0]);
        self::assertSame('tags', $conditions[0]->field);
        self::assertSame('page', $conditions[0]->value);

        self::assertInstanceOf(EqualCondition::class, $conditions[1]);
        self::assertSame('tags', $conditions[1]->field);
        self::assertSame('news', $conditions[1]->value);
    }

    /**
     * Validates: Requirements 2.1, 2.2
     */
    public function testEmptyTagConfigurationReturnsEmptyConditions(): void
    {
        $filterItem = [
            'uid' => 10,
            'tags' => '',
        ];

        $request = $this->createStubServerRequest(10, ['page', 'file']);
        $conditions = $this->subject->getFilterConfiguration($filterItem, $request);

        self::assertSame([], $conditions);
    }

    /**
     * Validates: Requirement 2.2
     */
    public function testRequestWithoutParsedBodyReturnsEmptyConditions(): void
    {
        $filterItem = [
            'uid' => 77,
            'tags' => "page=Seiten\nfile=Dateien",
        ];

        $request = $this->createStub(RequestInterface::class);
        $conditions = $this->subject->getFilterConfiguration($filterItem, $request);

        self::assertSame([], $conditions);
    }

    /**
     * Creates a stub ServerRequestInterface with the given selected values.
     *
     * @param int $uid
     * @param array<int, string> $selectedValues
     */
    private function createStubServerRequest(int $uid, array $selectedValues): ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([
            'tx_seal_search' => [
                'field_' . $uid => $selectedValues,
            ],
        ]);

        return $request;
    }
}
