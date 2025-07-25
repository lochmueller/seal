<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Command;

use Lochmueller\Seal\Indexing\Database\DatabaseIndexing;
use Lochmueller\Seal\Indexing\Web\WebIndexing;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsCommand(
    name: 'seal:index',
    description: 'Index content to SEAL adapters',
)]
class IndexCommand extends Command
{
    public function __construct(
        protected SiteFinder       $siteFinder,
        protected DatabaseIndexing $databaseIndexing,
        protected WebIndexing      $webIndexing,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('siteIdentifiers', InputArgument::OPTIONAL, 'Site identifier seperated by comma (,). Empty string -> all sites are checked', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $siteIdentifiers = GeneralUtility::trimExplode(',', (string) $input->getArgument('siteIdentifiers'), true);
        if (empty($siteIdentifiers)) {
            $sites = $this->siteFinder->getAllSites();
        } else {
            $sites = array_map(function ($siteId) {
                return $this->siteFinder->getSiteByIdentifier($siteId);
            }, $siteIdentifiers);
        }

        foreach ($sites as $site) {
            /** @var Site $site */
            $indexType = $site->getConfiguration()['sealIndexType'] ?? '';
            if ($indexType === 'database') {
                $this->databaseIndexing->indexDatabase($site);
            } elseif ($indexType === 'web') {
                $this->webIndexing->fillQueueForWebIndexing($site);
            }
        }

        return Command::SUCCESS;
    }
}
