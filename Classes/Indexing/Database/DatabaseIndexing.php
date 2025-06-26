<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Indexing\Database;

use Lochmueller\Seal\Indexing\Database\Types\Page;
use Lochmueller\Seal\Indexing\IndexingInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class DatabaseIndexing implements IndexingInterface
{
    public function indexDatabase(SiteInterface $site): void
    {


        foreach ($this->loadTypes($site) as $type) {
            foreach ($type->getItems() as $item) {

            }

        }


    }

    protected function loadTypes(SiteInterface $site): iterable
    {

        foreach ($this->loadTypeConfiguration($site) as $configRecord) {

            // Handle type


            yield new Page($configRecord);

        }
    }

    protected function loadTypeConfiguration(SiteInterface $site): array
    {

        // internal cache for all

        // get all for the specidic page


        return [];

    }

}
