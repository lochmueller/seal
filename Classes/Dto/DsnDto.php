<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Dto;

class DsnDto
{
    /**
     * @param array<string, array<mixed>|string> $query
     */
    public function __construct(
        public readonly string  $scheme,
        #[\SensitiveParameter]
        public readonly ?string $user = null,
        #[\SensitiveParameter]
        public readonly ?string $password = null,
        public readonly ?string $host = null,
        public readonly ?int    $port = null,
        public readonly ?string $path = null,
        public readonly array   $query = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'scheme' => $this->scheme,
            'user' => $this->user,
            'pass' => $this->password,
            'host' => $this->host,
            'port' => $this->port,
            'path' => $this->path,
            'query' => $this->query,
        ];
    }
}
