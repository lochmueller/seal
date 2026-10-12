<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\ViewHelpers;

use Lochmueller\Seal\Highlight\Highlighting;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use Lochmueller\Seal\ViewHelpers\HighlightViewHelper;

class HighlightViewHelperTest extends AbstractTest
{
    public function testRenderDelegatesToHighlighting(): void
    {
        $item = ['content' => 'Hello TYPO3'];

        $highlighting = $this->createMock(Highlighting::class);
        $highlighting->expects(self::once())
            ->method('renderField')
            ->with($item, 'content', 300)
            ->willReturn('Hello <mark>TYPO3</mark>');

        $subject = new HighlightViewHelper($highlighting);
        $subject->setArguments(['item' => $item, 'field' => 'content', 'crop' => 300]);

        self::assertSame('Hello <mark>TYPO3</mark>', $subject->render());
    }

    public function testRegistersArguments(): void
    {
        $subject = new HighlightViewHelper($this->createStub(Highlighting::class));
        $arguments = $subject->prepareArguments();

        self::assertTrue($arguments['item']->isRequired());
        self::assertTrue($arguments['field']->isRequired());
        self::assertFalse($arguments['crop']->isRequired());
        self::assertSame(0, $arguments['crop']->getDefaultValue());
    }

    public function testOutputIsNotEscapedByFluid(): void
    {
        $subject = new HighlightViewHelper($this->createStub(Highlighting::class));

        self::assertFalse($subject->isOutputEscapingEnabled());
    }
}
