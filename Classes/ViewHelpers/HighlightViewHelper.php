<?php

declare(strict_types=1);

namespace Lochmueller\Seal\ViewHelpers;

use Lochmueller\Seal\Highlight\Highlighting;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Renders a field of a search result document including the keyword highlighting.
 *
 * Example: <seal:highlight item="{item}" field="content" crop="300"/>
 */
class HighlightViewHelper extends AbstractViewHelper
{
    /**
     * The escaping is handled by the Highlighting service, so that only the "<mark>" tags
     * are rendered as markup and every other part of the value stays escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function __construct(private readonly Highlighting $highlighting) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('item', 'array', 'Search result document', true);
        $this->registerArgument('field', 'string', 'Name of the field that should be rendered', true);
        $this->registerArgument('crop', 'int', 'Maximum length of the rendered text, cropped around the first hit. 0 disables cropping.', false, 0);
    }

    public function render(): string
    {
        return $this->highlighting->renderField(
            (array) $this->arguments['item'],
            (string) $this->arguments['field'],
            (int) $this->arguments['crop'],
        );
    }
}
