<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Dashboard\Provider;

/**
 * Data provider of the SEAL TableWidget.
 */
interface TableDataProviderInterface
{
    /**
     * Column headers (LLL references or plain labels).
     *
     * @return list<string>
     */
    public function getHeaders(): array;

    /**
     * Table rows, every row has one value per header.
     *
     * @return list<list<string>>
     */
    public function getRows(): array;
}
