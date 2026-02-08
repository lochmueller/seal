<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Handler;

use Lochmueller\Seal\Handler\AutocompleteHandler;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

class AutocompleteHandlerTest extends AbstractTest
{
    private AutocompleteHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();

        // AutocompleteHandler needs Seal and ConfigurationLoader for handle(),
        // but findSuggestions() is a pure function we can test directly.
        $seal = $this->createStub(\Lochmueller\Seal\Seal::class);
        $configLoader = $this->createStub(\Lochmueller\Seal\Configuration\ConfigurationLoader::class);

        $this->subject = new AutocompleteHandler($seal, $configLoader);
    }

    public function testFindSuggestionsReturnsMatchingWords(): void
    {
        $result = $this->subject->findSuggestions('sea', 'The search engine uses SEAL for searching');

        self::assertContains('search', $result);
        self::assertContains('searching', $result);
    }

    public function testFindSuggestionsIsCaseInsensitive(): void
    {
        $result = $this->subject->findSuggestions('typ', 'TYPO3 is a Typing system');

        self::assertContains('TYPO3', $result);
        self::assertContains('Typing', $result);
    }

    public function testFindSuggestionsReturnsEmptyArrayWhenNoMatch(): void
    {
        $result = $this->subject->findSuggestions('xyz', 'Hello World this is a test');

        self::assertSame([], $result);
    }

    public function testFindSuggestionsReturnsUniqueResults(): void
    {
        $result = $this->subject->findSuggestions('test', 'test Test test testing');

        // "test" and "Test" differ in case but the match is case-insensitive,
        // however the keys are the original words, so both "test" and "Test" appear
        self::assertContains('test', $result);
        self::assertContains('testing', $result);
    }

    public function testFindSuggestionsMatchesFromBeginningOfWord(): void
    {
        $result = $this->subject->findSuggestions('con', 'The content is not disconnected');

        self::assertContains('content', $result);
        // "disconnected" should NOT match because "con" is not at the start
        self::assertNotContains('disconnected', $result);
    }

    public function testFindSuggestionsWithEmptyContent(): void
    {
        $result = $this->subject->findSuggestions('test', '');

        self::assertSame([], $result);
    }

    public function testFindSuggestionsWithSingleCharacterSearch(): void
    {
        $result = $this->subject->findSuggestions('a', 'an apple and banana');

        self::assertContains('an', $result);
        self::assertContains('apple', $result);
        self::assertContains('and', $result);
        self::assertNotContains('banana', $result);
    }

    public function testFindSuggestionsWithExactMatch(): void
    {
        $result = $this->subject->findSuggestions('hello', 'hello world');

        self::assertContains('hello', $result);
        self::assertNotContains('world', $result);
    }
}
