<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Adapter\Typo3;

use CmsIg\Seal\Adapter\AdapterInterface;
use Lochmueller\Seal\Adapter\Typo3\Typo3Adapter;
use Lochmueller\Seal\Adapter\Typo3\Typo3AdapterFactory;
use Lochmueller\Seal\Tests\Unit\AbstractTest;

class Typo3AdapterFactoryTest extends AbstractTest
{
    public function testCreateAdapterReturnsInjectedAdapter(): void
    {
        $adapter = $this->createStub(Typo3Adapter::class);
        $subject = new Typo3AdapterFactory($adapter);

        $result = $subject->createAdapter(['scheme' => 'typo3']);

        self::assertInstanceOf(AdapterInterface::class, $result);
        self::assertSame($adapter, $result);
    }

    public function testCreateAdapterIgnoresDsnParameter(): void
    {
        $adapter = $this->createStub(Typo3Adapter::class);
        $subject = new Typo3AdapterFactory($adapter);

        $result1 = $subject->createAdapter(['scheme' => 'typo3']);
        $result2 = $subject->createAdapter(['scheme' => 'other', 'host' => 'localhost']);

        self::assertSame($result1, $result2);
    }

    public function testGetNameReturnsTypo3(): void
    {
        self::assertSame('typo3', Typo3AdapterFactory::getName());
    }
}
