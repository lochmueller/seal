<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Indexing\Web;

use Lochmueller\Seal\Indexing\IndexingInterface;
use Lochmueller\Seal\Queue\Message\WebIndexMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class WebIndexing implements IndexingInterface
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {}

    public function fillQueueForWebIndexing(SiteInterface $site): void
    {


        // @todo handle the message
        $this->bus->dispatch(new WebIndexMessage());


        // Fill the queue for web requests

    }

    public function handleMessage(WebIndexMessage $message): void
    {
        DebuggerUtility::var_dump($message);
        die();
        //
    }

}
