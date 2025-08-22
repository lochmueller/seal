<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class StartController extends ActionController
{
    public function startAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }
}
