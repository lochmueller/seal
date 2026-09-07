<?php

declare(strict_types=1);

namespace Lochmueller\Seal\ViewHelpers\Uri;

use Lochmueller\Seal\Uri\SearchUriBuilder;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Renders the URI of a search result page including the complete current search state.
 *
 * Example: <seal:uri.search page="2" section="seal-12-results"/>
 */
class SearchViewHelper extends AbstractViewHelper
{
    public function __construct(private readonly SearchUriBuilder $searchUriBuilder) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('page', 'int', 'Result page the URI should point to', false, 1);
        $this->registerArgument('section', 'string', 'Anchor that is appended to the URI', false, '');
    }

    public function render(): string
    {
        $renderingContext = $this->renderingContext;
        if ($renderingContext === null || !$renderingContext->hasAttribute(ServerRequestInterface::class)) {
            return '';
        }

        $request = $renderingContext->getAttribute(ServerRequestInterface::class);
        if (!$request instanceof RequestInterface) {
            return '';
        }

        return $this->searchUriBuilder->buildResultPageUri(
            $request,
            (int) $this->arguments['page'],
            (string) $this->arguments['section'],
        );
    }
}
