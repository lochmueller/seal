<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit;

use CmsIg\Seal\EngineInterface;
use Lochmueller\Seal\Engine\EngineFactory;
use Lochmueller\Seal\Seal;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class SealTest extends AbstractTest
{
    private EngineFactory $engineFactoryStub;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engineFactoryStub = $this->createStub(EngineFactory::class);
    }

    public function testBuildEngineBySiteReturnsEngineFromFactory(): void
    {
        $engine = $this->createStub(EngineInterface::class);
        $this->engineFactoryStub->method('buildEngineBySite')->willReturn($engine);

        $site = $this->createStub(SiteInterface::class);
        $site->method('getIdentifier')->willReturn('main');

        $subject = new Seal($this->engineFactoryStub);

        $result = $subject->buildEngineBySite($site);

        self::assertSame($engine, $result);
    }

    public function testBuildEngineBySiteCachesResultForSameSite(): void
    {
        $engine = $this->createStub(EngineInterface::class);

        $engineFactory = $this->createMock(EngineFactory::class);
        $engineFactory->expects(self::once())
            ->method('buildEngineBySite')
            ->willReturn($engine);

        $site = $this->createStub(SiteInterface::class);
        $site->method('getIdentifier')->willReturn('main');

        $subject = new Seal($engineFactory);

        $first = $subject->buildEngineBySite($site);
        $second = $subject->buildEngineBySite($site);

        self::assertSame($first, $second);
    }

    public function testBuildEngineBySiteReturnsDifferentEnginesForDifferentSites(): void
    {
        $engineA = $this->createStub(EngineInterface::class);
        $engineB = $this->createStub(EngineInterface::class);

        $siteA = $this->createStub(SiteInterface::class);
        $siteA->method('getIdentifier')->willReturn('site-a');

        $siteB = $this->createStub(SiteInterface::class);
        $siteB->method('getIdentifier')->willReturn('site-b');

        $engineFactory = $this->createStub(EngineFactory::class);
        $engineFactory->method('buildEngineBySite')
            ->willReturnMap([
                [$siteA, $engineA],
                [$siteB, $engineB],
            ]);

        $subject = new Seal($engineFactory);

        $resultA = $subject->buildEngineBySite($siteA);
        $resultB = $subject->buildEngineBySite($siteB);

        self::assertSame($engineA, $resultA);
        self::assertSame($engineB, $resultB);
        self::assertNotSame($resultA, $resultB);
    }

    public function testBuildEngineBySiteCachesPerSiteIdentifier(): void
    {
        $engine = $this->createStub(EngineInterface::class);

        $engineFactory = $this->createMock(EngineFactory::class);
        $engineFactory->expects(self::exactly(2))
            ->method('buildEngineBySite')
            ->willReturn($engine);

        $siteA = $this->createStub(SiteInterface::class);
        $siteA->method('getIdentifier')->willReturn('alpha');

        $siteB = $this->createStub(SiteInterface::class);
        $siteB->method('getIdentifier')->willReturn('beta');

        $subject = new Seal($engineFactory);

        // Each site triggers one factory call
        $subject->buildEngineBySite($siteA);
        $subject->buildEngineBySite($siteB);

        // Repeated calls use cache — no additional factory calls
        $subject->buildEngineBySite($siteA);
        $subject->buildEngineBySite($siteB);
    }
}
