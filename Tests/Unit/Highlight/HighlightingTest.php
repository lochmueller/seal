<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Highlight;

use Lochmueller\Seal\Highlight\Highlighting;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

class HighlightingTest extends AbstractTest
{
    private Highlighting $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new Highlighting();
    }

    private function wrap(string $value): string
    {
        return Highlighting::PRE_TAG . $value . Highlighting::POST_TAG;
    }

    public function testRenderConvertsSentinelTagsToMarkup(): void
    {
        $formatted = 'The ' . $this->wrap('TYPO3') . ' homepage';

        self::assertSame('The <mark>TYPO3</mark> homepage', $this->subject->render($formatted, 'The TYPO3 homepage'));
    }

    public function testRenderUsesFallbackWithoutHighlightInformation(): void
    {
        self::assertSame('The TYPO3 homepage', $this->subject->render(null, 'The TYPO3 homepage'));
    }

    public function testRenderEscapesFallbackValue(): void
    {
        self::assertSame(
            '&lt;script&gt;alert(1)&lt;/script&gt;',
            $this->subject->render(null, '<script>alert(1)</script>'),
        );
    }

    public function testRenderEscapesEverythingButTheHighlight(): void
    {
        $formatted = '<script>alert(1)</script> and ' . $this->wrap('TYPO3');

        self::assertSame(
            '&lt;script&gt;alert(1)&lt;/script&gt; and <mark>TYPO3</mark>',
            $this->subject->render($formatted, ''),
        );
    }

    public function testRenderHandlesMultipleHighlights(): void
    {
        $formatted = $this->wrap('TYPO3') . ' and ' . $this->wrap('SEAL');

        self::assertSame('<mark>TYPO3</mark> and <mark>SEAL</mark>', $this->subject->render($formatted, ''));
    }

    public function testRenderReturnsEmptyStringForEmptyValues(): void
    {
        self::assertSame('', $this->subject->render(null, ''));
    }

    public function testRenderDoesNotCropShortValues(): void
    {
        self::assertSame('Short text', $this->subject->render(null, 'Short text', 100));
    }

    public function testRenderCropsPlainValueFromTheBeginning(): void
    {
        $value = str_repeat('word ', 50);

        $result = $this->subject->render(null, $value, 20);

        self::assertStringStartsWith('word word', $result);
        self::assertStringEndsWith('…', $result);
        self::assertLessThan(30, mb_strlen($result));
    }

    public function testRenderCropsAroundTheFirstHighlight(): void
    {
        $before = str_repeat('before ', 30);
        $after = str_repeat('after ', 30);
        $formatted = $before . $this->wrap('needle') . ' ' . $after;

        $result = $this->subject->render($formatted, '', 60);

        self::assertStringContainsString('<mark>needle</mark>', $result);
        self::assertStringStartsWith('…', $result);
        self::assertStringEndsWith('…', $result);
    }

    public function testRenderNeverSplitsTheHighlightWhileCropping(): void
    {
        $formatted = str_repeat('a ', 100) . $this->wrap('needle') . str_repeat(' b', 100);

        $result = $this->subject->render($formatted, '', 40);

        self::assertSame(1, substr_count($result, '<mark>'));
        self::assertSame(1, substr_count($result, '</mark>'));
        self::assertStringContainsString('<mark>needle</mark>', $result);
    }

    public function testRenderKeepsMultibyteCharactersIntact(): void
    {
        $formatted = 'Über ' . $this->wrap('Größe') . ' und Maße für die Prüfung der Änderungen';

        $result = $this->subject->render($formatted, '', 20);

        self::assertStringContainsString('<mark>Größe</mark>', $result);
        self::assertSame($result, mb_convert_encoding($result, 'UTF-8', 'UTF-8'));
    }

    public function testRenderFieldReadsFormattedValueFromDocument(): void
    {
        $document = [
            'title' => 'The TYPO3 homepage',
            Highlighting::RESULT_KEY => [
                'title' => 'The ' . $this->wrap('TYPO3') . ' homepage',
            ],
        ];

        self::assertSame('The <mark>TYPO3</mark> homepage', $this->subject->renderField($document, 'title'));
    }

    public function testRenderFieldFallsBackToRawValueOnNullHighlight(): void
    {
        $document = [
            'title' => 'The TYPO3 homepage',
            Highlighting::RESULT_KEY => [
                'title' => null,
            ],
        ];

        self::assertSame('The TYPO3 homepage', $this->subject->renderField($document, 'title'));
    }

    public function testRenderFieldFallsBackToRawValueWithoutFormattedKey(): void
    {
        self::assertSame('The TYPO3 homepage', $this->subject->renderField(['title' => 'The TYPO3 homepage'], 'title'));
    }

    public function testRenderFieldReturnsEmptyStringForUnknownField(): void
    {
        self::assertSame('', $this->subject->renderField(['title' => 'Title'], 'content'));
    }

    public function testRenderFieldCastsScalarValues(): void
    {
        self::assertSame('123', $this->subject->renderField(['size' => 123], 'size'));
    }
}
