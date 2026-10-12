<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Dashboard\Widget;

use Lochmueller\Seal\Dashboard\Provider\TableDataProviderInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

/**
 * Renders the rows of a data provider as table.
 *
 * The core ListWidget is not used on purpose: its template "Widget/ListWidget" is a common target
 * for template overrides of other extensions (e.g. EXT:ke_search replaces it with a table of its
 * own statistics), so the SEAL widgets ship their own template under a unique name.
 */
class TableWidget implements WidgetInterface, RequestAwareWidgetInterface
{
    private ServerRequestInterface $request;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly TableDataProviderInterface $dataProvider,
        private readonly BackendViewFactory $backendViewFactory,
        private readonly array $options = [],
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $view = $this->backendViewFactory->create($this->request, ['typo3/cms-dashboard', 'lochmueller/seal']);
        $view->assignMultiple([
            'headers' => $this->dataProvider->getHeaders(),
            'rows' => $this->dataProvider->getRows(),
            'options' => $this->options,
            'configuration' => $this->configuration,
        ]);
        return $view->render('Widget/SealTableWidget');
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
