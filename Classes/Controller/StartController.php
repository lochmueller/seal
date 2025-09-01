<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Controller;

use Psr\Http\Message\ResponseInterface;

class StartController extends AbstractSealController
{
    public function startAction(): ResponseInterface
    {
        $this->view->assignMultiple([
            'filters' => iterator_to_array($this->getFilterRowsByContentElementUid($this->getCurrentContentElementRow()['uid'])),
        ]);

        return $this->htmlResponse();
    }
}
