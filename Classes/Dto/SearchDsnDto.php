<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Dto;

class SearchDsnDto
{
    public ?string $scheme = null;
    public ?string $host = null;
    public ?string $port = null;
    public ?string $path = null;
    public ?string $user = null;
    public ?string $pass = null;
    public ?string $query = null;
    public ?string $fragment = null;
    public array $options = [];

    public function __construct(protected string $searchDsn)
    {
        $this->extractDsn();
    }

    protected function extractDsn(): void
    {
        $parts = parse_url($this->searchDsn);
        if ($parts === false) {
            if (preg_match('/^([a-z0-9]*):\/\/.*/', $this->searchDsn, $matches)) {
                $parts = [
                    'scheme' => $matches[1],
                ];
            }
        }

        $this->scheme = $parts['scheme'] ?? null;
        $this->host = $parts['host'] ?? null;
        $this->port = $parts['port'] ?? null;
        $this->path = $parts['path'] ?? null;
        parse_str($parts['query'] ?? '', $this->options);
    }

}
