<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Exception;

use Lochmueller\Seal\Exception\AdapterDependenciesNotFoundException;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

class AdapterDependenciesNotFoundExceptionTest extends AbstractTest
{
    public function testWithPackageGeneratesMessage(): void
    {
        $exception = new AdapterDependenciesNotFoundException(package: 'cmsig/seal-loupe-adapter');

        self::assertStringContainsString('composer require cmsig/seal-loupe-adapter', $exception->getMessage());
    }

    public function testWithCustomMessage(): void
    {
        $exception = new AdapterDependenciesNotFoundException(message: 'Custom error');

        self::assertSame('Custom error', $exception->getMessage());
    }

    public function testWithCodeAndPrevious(): void
    {
        $previous = new \RuntimeException('root cause');
        $exception = new AdapterDependenciesNotFoundException(message: 'fail', code: 42, previous: $previous);

        self::assertSame(42, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
    }

    public function testPackageOverridesMessage(): void
    {
        $exception = new AdapterDependenciesNotFoundException(message: 'ignored', package: 'vendor/pkg');

        self::assertStringContainsString('composer require vendor/pkg', $exception->getMessage());
        self::assertStringNotContainsString('ignored', $exception->getMessage());
    }

    public function testIsInstanceOfException(): void
    {
        $exception = new AdapterDependenciesNotFoundException();

        self::assertInstanceOf(\Exception::class, $exception);
    }
}
