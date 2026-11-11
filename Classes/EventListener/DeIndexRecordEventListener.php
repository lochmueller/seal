<?php

declare(strict_types=1);

namespace Lochmueller\Seal\EventListener;

use Lochmueller\Index\Event\DeIndexRecordEvent;
use Lochmueller\Seal\Seal;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;

final class DeIndexRecordEventListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly Seal $seal,
    ) {}

    #[AsEventListener('seal-deindex-record')]
    public function __invoke(DeIndexRecordEvent $event): void
    {
        try {
            $engine = $this->seal->buildEngineBySite($event->site);
            $identifier = 'r-' . $event->table . '-' . $event->recordUid . '-' . $event->language;
            $engine->deleteDocument($this->seal->getIndexNameBySite($event->site), $identifier);
        } catch (\Exception $exception) {
            if ($this->isDocumentNotFound($exception)) {
                $this->logger?->debug('Record document was already absent during de-indexing.', [
                    'exception' => $exception,
                    'table' => $event->table,
                    'recordUid' => $event->recordUid,
                ]);
                return;
            }

            $this->logger?->error($exception->getMessage(), [
                'exception' => $exception,
                'table' => $event->table,
                'recordUid' => $event->recordUid,
            ]);
            throw $exception;
        }
    }

    private function isDocumentNotFound(\Exception $exception): bool
    {
        if (!method_exists($exception, 'getStatusCode') || $exception->getStatusCode() !== 404) {
            return false;
        }

        $response = json_decode($exception->getMessage(), true);
        return is_array($response) && ($response['result'] ?? null) === 'not_found';
    }
}
