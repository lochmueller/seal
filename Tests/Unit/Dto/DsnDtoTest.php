<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Dto;

use Lochmueller\Seal\Dto\DsnDto;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

class DsnDtoTest extends AbstractTest
{
    public function testConstructorUsesDefaults(): void
    {
        $subject = new DsnDto('typo3');

        self::assertSame('typo3', $subject->scheme);
        self::assertNull($subject->user);
        self::assertNull($subject->pass);
        self::assertNull($subject->host);
        self::assertNull($subject->port);
        self::assertNull($subject->path);
        self::assertSame([], $subject->query);
    }

    public function testToArrayReturnsAllProperties(): void
    {
        $subject = new DsnDto(
            scheme: 'elasticsearch',
            user: 'admin',
            pass: 'secret',
            host: 'localhost',
            port: 9200,
            path: '/prefix',
            query: ['tls' => 'true', 'hosts' => ['a', 'b']],
        );

        self::assertSame([
            'scheme' => 'elasticsearch',
            'user' => 'admin',
            'pass' => 'secret',
            'host' => 'localhost',
            'port' => 9200,
            'path' => '/prefix',
            'query' => ['tls' => 'true', 'hosts' => ['a', 'b']],
        ], $subject->toArray());
    }
}
