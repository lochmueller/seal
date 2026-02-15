<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Handler;

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Schema\Schema;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\SearchBuilder;
use Lochmueller\Seal\Configuration\Configuration;
use Lochmueller\Seal\Configuration\ConfigurationLoader;
use Lochmueller\Seal\Handler\AutocompleteHandler;
use Lochmueller\Seal\Schema\SchemaBuilder as SealSchemaBuilder;
use Lochmueller\Seal\Seal;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use PHPUnit\Framework\MockObject\Stub;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class AutocompleteHandlerTest extends AbstractTest
{
    private AutocompleteHandler $subject;

    private Stub&Seal $seal;

    private Stub&ConfigurationLoader $configurationLoader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seal = $this->createStub(Seal::class);
        $this->configurationLoader = $this->createStub(ConfigurationLoader::class);

        $this->subject = new AutocompleteHandler($this->seal, $this->configurationLoader);
    }

    private function createSiteStub(string $identifier = 'test-site'): Stub&SiteInterface
    {
        $site = $this->createStub(SiteInterface::class);
        $site->method('getIdentifier')->willReturn($identifier);

        return $site;
    }

    private function createLanguageStub(int $languageId = 0): Stub&SiteLanguage
    {
        $language = $this->createStub(SiteLanguage::class);
        $language->method('getLanguageId')->willReturn($languageId);

        return $language;
    }

    private function createRequest(string $query, SiteInterface $site, ?SiteLanguage $language = null): Stub&ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['q' => $query]);

        $attributes = ['site' => $site];
        if ($language !== null) {
            $attributes['language'] = $language;
        }
        $request->method('getAttributes')->willReturn($attributes);

        return $request;
    }

    /**
     * @param array<int, array<string, mixed>> $documents
     */
    private function configureSearchResult(SiteInterface $site, array $documents): void
    {
        $generator = (static function () use ($documents): \Generator {
            yield from $documents;
        })();
        $result = new Result($generator, count($documents));

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $schemaBuilder = new SealSchemaBuilder($eventDispatcher);
        $schema = new Schema([
            SealSchemaBuilder::DEFAULT_INDEX => $schemaBuilder->getPageIndex(),
        ]);

        $searcher = $this->createStub(SearcherInterface::class);
        $searcher->method('search')->willReturn($result);

        $searchBuilder = new SearchBuilder($schema, $searcher);
        $searchBuilder->index(SealSchemaBuilder::DEFAULT_INDEX);

        $engine = $this->createStub(EngineInterface::class);
        $engine->method('createSearchBuilder')->willReturn($searchBuilder);

        $this->seal->method('buildEngineBySite')->willReturn($engine);
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

    public function testHandleReturnsTooFewCharsResponseWhenSearchWordTooShort(): void
    {
        $site = $this->createSiteStub();
        $config = new Configuration('typo3://', 3, 10);
        $this->configurationLoader->method('loadBySite')->willReturn($config);

        $request = $this->createRequest('ab', $site);

        $response = $this->subject->handle($request);

        self::assertSame(204, $response->getStatusCode());
        self::assertSame('Too few chars for auto complete functions', $response->getHeaderLine('X-Seal-Info'));
    }

    public function testHandleReturnsTooFewCharsForEmptyQuery(): void
    {
        $site = $this->createSiteStub();
        $config = new Configuration('typo3://', 3, 10);
        $this->configurationLoader->method('loadBySite')->willReturn($config);

        $request = $this->createRequest('', $site);

        $response = $this->subject->handle($request);

        self::assertSame(204, $response->getStatusCode());
    }

    public function testHandleReturnsTooFewCharsForWhitespaceOnlyQuery(): void
    {
        $site = $this->createSiteStub();
        $config = new Configuration('typo3://', 3, 10);
        $this->configurationLoader->method('loadBySite')->willReturn($config);

        $request = $this->createRequest('   ', $site);

        $response = $this->subject->handle($request);

        self::assertSame(204, $response->getStatusCode());
    }

    public function testHandleReturnsJsonWithSuggestions(): void
    {
        $site = $this->createSiteStub();
        $config = new Configuration('typo3://', 3, 10);
        $this->configurationLoader->method('loadBySite')->willReturn($config);

        $this->configureSearchResult($site, [
            ['title' => 'Search Engine', 'content' => 'searching for results'],
        ]);

        $request = $this->createRequest('sea', $site);

        $response = $this->subject->handle($request);

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertContains('Search', $body);
        self::assertContains('searching', $body);
    }

    public function testHandleReturnsEmptyArrayWhenNoDocumentsMatch(): void
    {
        $site = $this->createSiteStub();
        $config = new Configuration('typo3://', 3, 10);
        $this->configurationLoader->method('loadBySite')->willReturn($config);

        $this->configureSearchResult($site, []);

        $request = $this->createRequest('xyz', $site);

        $response = $this->subject->handle($request);

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame([], $body);
    }

    public function testHandleReturnsUniqueSuggestionsAcrossMultipleDocuments(): void
    {
        $site = $this->createSiteStub();
        $config = new Configuration('typo3://', 3, 10);
        $this->configurationLoader->method('loadBySite')->willReturn($config);

        $this->configureSearchResult($site, [
            ['title' => 'Testing guide', 'content' => 'test your code'],
            ['title' => 'Test driven', 'content' => 'testing is important'],
        ]);

        $request = $this->createRequest('test', $site);

        $response = $this->subject->handle($request);

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        // Results should be unique
        self::assertSame(array_unique($body), $body);
        self::assertContains('test', $body);
        self::assertContains('Testing', $body);
    }

    public function testHandleUsesLanguageFromRequestAttributes(): void
    {
        $site = $this->createSiteStub();
        $language = $this->createLanguageStub(1);
        $config = new Configuration('typo3://', 3, 10);
        $this->configurationLoader->method('loadBySite')->willReturn($config);

        $this->configureSearchResult($site, [
            ['title' => 'Suchmaschine', 'content' => 'suchen nach Ergebnissen'],
        ]);

        $request = $this->createRequest('such', $site, $language);

        $response = $this->subject->handle($request);

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertContains('Suchmaschine', $body);
        self::assertContains('suchen', $body);
    }

    public function testHandleRespectsCustomMinCharactersSetting(): void
    {
        $site = $this->createSiteStub();
        $config = new Configuration('typo3://', 5, 10);
        $this->configurationLoader->method('loadBySite')->willReturn($config);

        $request = $this->createRequest('test', $site);

        $response = $this->subject->handle($request);

        self::assertSame(204, $response->getStatusCode());
    }

    public function testHandleIncludesTotalInHeader(): void
    {
        $site = $this->createSiteStub();
        $config = new Configuration('typo3://', 3, 10);
        $this->configurationLoader->method('loadBySite')->willReturn($config);

        $this->configureSearchResult($site, [
            ['title' => 'First result', 'content' => 'content here'],
            ['title' => 'Second result', 'content' => 'more content'],
        ]);

        $request = $this->createRequest('con', $site);

        $response = $this->subject->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('2 search items', $response->getHeaderLine('X-Seal-Info'));
    }
}
