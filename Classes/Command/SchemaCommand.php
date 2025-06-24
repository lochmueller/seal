<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Command;

use Lochmueller\Seal\Seal;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Site\SiteFinder;

#[AsCommand(
    name: 'seal:schema',
    description: 'Update the schema in SEAL adapters',
)]
class SchemaCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(protected SiteFinder $siteFinder, protected Seal $seal)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->siteFinder->getAllSites() as $site) {
            try {
                $engine = $this->seal->buildEngineBySite($site);
                $engine->createSchema();
            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
