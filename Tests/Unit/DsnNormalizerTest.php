<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit;

use Lochmueller\Seal\DsnNormalizer;
use TYPO3\CMS\Core\Core\Environment;

class DsnNormalizerTest extends AbstractTest
{
    private DsnNormalizer $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new DsnNormalizer();
    }

    public function testRelativeLoupePathIsResolvedAgainstProjectPath(): void
    {
        self::assertSame(
            'loupe://' . Environment::getProjectPath() . '/var/loupe',
            $this->subject->normalize('loupe://var/loupe'),
        );
    }

    public function testLoupeDsnWithoutPathUsesDefaultDirectory(): void
    {
        self::assertSame(
            'loupe://' . Environment::getProjectPath() . '/var/loupe',
            $this->subject->normalize('loupe://'),
        );
    }

    public function testAbsoluteLoupePathIsKept(): void
    {
        self::assertSame(
            'loupe:///var/data/indices',
            $this->subject->normalize('loupe:///var/data/indices'),
        );
    }

    public function testQueryIsKept(): void
    {
        self::assertSame(
            'loupe://' . Environment::getProjectPath() . '/var/loupe?foo=bar',
            $this->subject->normalize('loupe://var/loupe?foo=bar'),
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('otherSchemeProvider')]
    public function testOtherSchemesAreNotTouched(string $dsn): void
    {
        self::assertSame($dsn, $this->subject->normalize($dsn));
    }

    /**
     * @return iterable<string, array{dsn: string}>
     */
    public static function otherSchemeProvider(): iterable
    {
        yield 'typo3' => ['dsn' => 'typo3://'];
        yield 'meilisearch' => ['dsn' => 'meilisearch://127.0.0.1:7700'];
        yield 'memory' => ['dsn' => 'memory://'];
        yield 'without scheme' => ['dsn' => 'invalid-dsn-without-scheme'];
    }
}
