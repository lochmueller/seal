<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Exception;

use Lochmueller\Seal\Exception\AdapterNotFoundException;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

class AdapterNotFoundExceptionTest extends AbstractTest
{
    public function testIsInstanceOfException(): void
    {
        $exception = new AdapterNotFoundException();

        self::assertInstanceOf(\Exception::class, $exception);
    }

    public function testWithMessage(): void
    {
        $exception = new AdapterNotFoundException('Adapter not found');

        self::assertSame('Adapter not found', $exception->getMessage());
    }

    public function testWithCodeAndPrevious(): void
    {
        $previous = new \RuntimeException('root cause');
        $exception = new AdapterNotFoundException('fail', 123, $previous);

        self::assertSame(123, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
    }
}
