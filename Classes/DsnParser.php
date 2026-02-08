<?php

declare(strict_types=1);

namespace Lochmueller\Seal;

use Lochmueller\Seal\Dto\DsnDto;

class DsnParser
{
    public function parse(string $dsn): DsnDto
    {
        $parts = parse_url($dsn);

        if ($parts === false || !isset($parts['scheme'])) {
            if (preg_match('/^([a-z0-9]+):\/\/.*/', $dsn, $matches)) {
                $parts = [
                    'scheme' => $matches[1],
                ];
            } else {
                throw new \InvalidArgumentException('Invalid DSN format');
            }
        }

        $query = [];
        parse_str($parts['query'] ?? '', $query);
        /** @var array<string, array<mixed>|string> $parsedQuery */
        $parsedQuery = $query;

        return new DsnDto(
            scheme: $parts['scheme'],
            user: $parts['user'] ?? null,
            password: $parts['pass'] ?? null,
            host: $parts['host'] ?? null,
            port: $parts['port'] ?? null,
            path: isset($parts['path']) ? ltrim($parts['path'], '/') : null,
            query: $parsedQuery,
        );
    }
}
