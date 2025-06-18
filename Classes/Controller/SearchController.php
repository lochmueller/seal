<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Controller;

use Lochmueller\Seal\Seal;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class SearchController extends ActionController
{

    public function __construct(private Seal $seal
    )
    {
    }

    public function listAction(): ResponseInterface
    {

        $engine = $this->seal->buildEngine();

        $result = $engine->createSearchBuilder('page')
            ->getResult();

        foreach ($result as $document) {
            #DebuggerUtility::var_dump($document);
        }

        #DebuggerUtility::var_dump($result->total());


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
