<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit;

use Lochmueller\Seal\DsnParser;
use Lochmueller\Seal\Dto\DsnDto;

class DsnParserTest extends AbstractTest
{
    private DsnParser $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new DsnParser();
    }

    public function testParseSimpleDsn(): void
    {
        $result = $this->subject->parse('mysql://localhost');

        self::assertInstanceOf(DsnDto::class, $result);
        self::assertSame('mysql', $result->scheme);
        self::assertSame('localhost', $result->host);
        self::assertNull($result->user);
        self::assertNull($result->password);
        self::assertNull($result->port);
        self::assertNull($result->path);
        self::assertSame([], $result->query);
    }

    public function testParseFullDsn(): void
    {
        $result = $this->subject->parse('mysql://user:password@localhost:3306/database?charset=utf8');

        self::assertSame('mysql', $result->scheme);
        self::assertSame('user', $result->user);
        self::assertSame('password', $result->password);
        self::assertSame('localhost', $result->host);
        self::assertSame(3306, $result->port);
        self::assertSame('database', $result->path);
        self::assertSame(['charset' => 'utf8'], $result->query);
    }

    public function testParseDsnWithMultipleQueryParameters(): void
    {
        $result = $this->subject->parse('elasticsearch://localhost:9200/index?timeout=30&retries=3');

        self::assertSame('elasticsearch', $result->scheme);
        self::assertSame('localhost', $result->host);
        self::assertSame(9200, $result->port);
        self::assertSame('index', $result->path);
        self::assertSame(['timeout' => '30', 'retries' => '3'], $result->query);
    }

    public function testParseDsnWithoutHost(): void
    {
        $result = $this->subject->parse('loupe://');

        self::assertSame('loupe', $result->scheme);
        self::assertNull($result->host);
    }

    public function testParseDsnWithPathOnly(): void
    {
        $result = $this->subject->parse('file:///var/data/index');

        self::assertSame('file', $result->scheme);
        self::assertSame('var/data/index', $result->path);
    }

    public function testParseDsnWithUserWithoutPassword(): void
    {
        $result = $this->subject->parse('redis://admin@localhost:6379');

        self::assertSame('redis', $result->scheme);
        self::assertSame('admin', $result->user);
        self::assertNull($result->password);
        self::assertSame('localhost', $result->host);
        self::assertSame(6379, $result->port);
    }

    public function testParseInvalidDsnThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid DSN format');

        $this->subject->parse('invalid-dsn-without-scheme');
    }

    public function testParseDsnWithSpecialCharactersInPassword(): void
    {
        $result = $this->subject->parse('mysql://user:p%40ss%3Aword@localhost/db');

        self::assertSame('user', $result->user);
        // Note: parse_url does not decode URL-encoded characters
        self::assertSame('p%40ss%3Aword', $result->password);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dsnSchemeProvider')]
    public function testParseVariousSchemes(string $dsn, string $expectedScheme): void
    {
        $result = $this->subject->parse($dsn);

        self::assertSame($expectedScheme, $result->scheme);
    }

    /**
     * @return iterable<string, array{dsn: string, expectedScheme: string}>
     */
    public static function dsnSchemeProvider(): iterable
    {
        yield 'algolia' => ['dsn' => 'algolia://app-id:api-key@localhost', 'expectedScheme' => 'algolia'];
        yield 'elasticsearch' => ['dsn' => 'elasticsearch://localhost:9200', 'expectedScheme' => 'elasticsearch'];
        yield 'meilisearch' => ['dsn' => 'meilisearch://localhost:7700', 'expectedScheme' => 'meilisearch'];
        yield 'opensearch' => ['dsn' => 'opensearch://localhost:9200', 'expectedScheme' => 'opensearch'];
        yield 'loupe' => ['dsn' => 'loupe://', 'expectedScheme' => 'loupe'];
        yield 'typesense' => ['dsn' => 'typesense://localhost:8108', 'expectedScheme' => 'typesense'];
        yield 'solr' => ['dsn' => 'solr://localhost:8983', 'expectedScheme' => 'solr'];
    }

    public function testDsnDtoToArray(): void
    {
        $result = $this->subject->parse('mysql://user:pass@localhost:3306/db?opt=val');

        $array = $result->toArray();

        self::assertSame([
            'scheme' => 'mysql',
            'user' => 'user',
            'pass' => 'pass',
            'host' => 'localhost',
            'port' => 3306,
            'path' => 'db',
            'query' => ['opt' => 'val'],
        ], $array);
    }
}
