<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class SearchController extends ActionController
{
    public function listAction(): ResponseInterface
    {
        $this->view->assignMultiple([
            // @todo
        ]);
        return $this->htmlResponse();
    }

    public function searchAction(): ResponseInterface
    {
        $this->view->assignMultiple([
            // @todo
        ]);
        return $this->htmlResponse();
    }

}
