<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Command;

use Lochmueller\Seal\Seal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Site\SiteFinder;

#[AsCommand(
    name: 'seal:index',
    description: 'Index content to SEAL adapters',
)]
class IndexCommand extends Command
{
    public function __construct(protected SiteFinder $siteFinder, protected Seal $seal)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        // @todo add indexing type
        // @todo add site selection
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        // @todo Databaseindexing
        // @todo webrequest indexing (EXT:crawler)
        foreach ($this->siteFinder->getAllSites() as $site) {
            $engine = $this->seal->buildEngineBySite($site);


            #$engine->bulk()
        }

        return Command::SUCCESS;
    }
}
