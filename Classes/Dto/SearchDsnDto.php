<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Dto;

class SearchDsnDto
{
    public ?string $type = null;
    public ?string $host = null;
    public ?string $port = null;
    public ?string $path = null;
    public array $options = [];

    public function __construct(protected string $searchDsn)
    {
        $this->extractDsn();
    }

    protected function extractDsn(): void
    {
        $parts = parse_url($this->searchDsn);

        $this->type = $parts['scheme'] ?? null;
        $this->host = $parts['host'] ?? null;
        $this->port = $parts['port'] ?? null;
        $this->path = $parts['path'] ?? null;
        parse_str($parts['query'] ?? '', $this->options);
    }

}
