<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\Command;

use CmsIg\Seal\EngineInterface;
use Lochmueller\Seal\Command\SchemaCommand;
use Lochmueller\Seal\Seal;
use Lochmueller\Seal\Tests\Unit\AbstractTest;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

class SchemaCommandTest extends AbstractTest
{
    private MockObject&SiteFinder $siteFinderMock;

    private MockObject&Seal $sealMock;

    private SchemaCommand $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->siteFinderMock = $this->createMock(SiteFinder::class);
        $this->sealMock = $this->createMock(Seal::class);

        $this->subject = new SchemaCommand($this->siteFinderMock, $this->sealMock);
    }

    public function testExecuteReturnsSuccessWithNoSites(): void
    {
        $this->siteFinderMock
            ->expects(self::once())
            ->method('getAllSites')
            ->willReturn([]);

        $this->sealMock->expects(self::never())->method('buildEngineBySite');

        $result = $this->invokeExecute($this->createStub(InputInterface::class), $this->createStub(OutputInterface::class));

        self::assertSame(Command::SUCCESS, $result);
    }

    public function testExecuteCreatesSchemaForEachSite(): void
    {
        $site1 = $this->createStub(Site::class);
        $site2 = $this->createStub(Site::class);

        $engine1 = $this->createMock(EngineInterface::class);
        $engine2 = $this->createMock(EngineInterface::class);

        $this->siteFinderMock
            ->expects(self::once())
            ->method('getAllSites')
            ->willReturn([$site1, $site2]);

        $this->sealMock
            ->expects(self::exactly(2))
            ->method('buildEngineBySite')
            ->willReturnCallback(fn($site) => match ($site) {
                $site1 => $engine1,
                $site2 => $engine2,
                default => throw new \LogicException('Unexpected site'),
            });

        $engine1->expects(self::once())->method('createSchema');
        $engine2->expects(self::once())->method('createSchema');

        $result = $this->invokeExecute($this->createStub(InputInterface::class), $this->createStub(OutputInterface::class));

        self::assertSame(Command::SUCCESS, $result);
    }

    public function testExecuteCatchesExceptionAndContinues(): void
    {
        $site1 = $this->createStub(Site::class);
        $site2 = $this->createStub(Site::class);

        $engine1 = $this->createMock(EngineInterface::class);
        $engine2 = $this->createMock(EngineInterface::class);

        $this->siteFinderMock
            ->expects(self::once())
            ->method('getAllSites')
            ->willReturn([$site1, $site2]);

        $this->sealMock
            ->expects(self::exactly(2))
            ->method('buildEngineBySite')
            ->willReturnCallback(fn($site) => match ($site) {
                $site1 => $engine1,
                $site2 => $engine2,
                default => throw new \LogicException('Unexpected site'),
            });

        $engine1->expects(self::once())
            ->method('createSchema')
            ->willThrowException(new \Exception('Test exception'));

        $engine2->expects(self::once())->method('createSchema');

        $result = $this->invokeExecute($this->createStub(InputInterface::class), $this->createStub(OutputInterface::class));

        self::assertSame(Command::SUCCESS, $result);
    }

    public function testExecuteLogsExceptionMessage(): void
    {
        $site = $this->createStub(Site::class);
        $engine = $this->createMock(EngineInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->siteFinderMock
            ->expects(self::once())
            ->method('getAllSites')
            ->willReturn([$site]);

        $this->sealMock
            ->expects(self::once())
            ->method('buildEngineBySite')
            ->with($site)
            ->willReturn($engine);

        $engine->expects(self::once())
            ->method('createSchema')
            ->willThrowException(new \Exception('Schema creation failed'));

        $logger->expects(self::once())
            ->method('info')
            ->with('Schema creation failed');

        $this->subject->setLogger($logger);

        $result = $this->invokeExecute($this->createStub(InputInterface::class), $this->createStub(OutputInterface::class));

        self::assertSame(Command::SUCCESS, $result);
    }

    public function testExecuteHandlesExceptionFromBuildEngineBySite(): void
    {
        $site = $this->createStub(Site::class);

        $this->siteFinderMock
            ->expects(self::once())
            ->method('getAllSites')
            ->willReturn([$site]);

        $this->sealMock
            ->expects(self::once())
            ->method('buildEngineBySite')
            ->with($site)
            ->willThrowException(new \Exception('Engine build failed'));

        $result = $this->invokeExecute($this->createStub(InputInterface::class), $this->createStub(OutputInterface::class));

        self::assertSame(Command::SUCCESS, $result);
    }

    private function invokeExecute(InputInterface $input, OutputInterface $output): int
    {
        $reflection = new \ReflectionMethod($this->subject, 'execute');

        return $reflection->invoke($this->subject, $input, $output);
    }
}
