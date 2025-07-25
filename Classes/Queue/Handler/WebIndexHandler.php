<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Queue\Handler;

use Lochmueller\Seal\Indexing\Web\WebIndexing;
use Lochmueller\Seal\Queue\Message\WebIndexMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class WebIndexHandler
{
    public function __construct(protected WebIndexing $webIndexing) {}

    public function __invoke(WebIndexMessage $message): void
    {
        $this->webIndexing->handleMessage($message);
    }
}
