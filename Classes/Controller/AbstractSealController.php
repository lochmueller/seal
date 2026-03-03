<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Controller;

use Lochmueller\Seal\Configuration\ConfigurationLoader;
use Lochmueller\Seal\Filter\Filter;
use Lochmueller\Seal\Filter\RadiusConfigurationParser;
use Lochmueller\Seal\Filter\TagConfigurationParser;
use Lochmueller\Seal\Seal;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

abstract class AbstractSealController extends ActionController
{

    public function __construct(
        private readonly TagConfigurationParser $tagConfigurationParser,
        private readonly RadiusConfigurationParser $radiusConfigurationParser,
    ) {}

    /**
     * @return array<string, mixed>
     */
    protected function getCurrentContentElementRow(): array
    {
        /** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $currentContentObject */
        $currentContentObject = $this->request->getAttribute('currentContentObject');
        return $currentContentObject->data;
    }

    /**
     * @return iterable<array<string, mixed>>
     */
    protected function getFilterRowsByContentElementUid(int $uid): iterable
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_seal_domain_model_filter');

        $where = [
            $queryBuilder->expr()->eq('parent', $queryBuilder->createNamedParameter($uid)),
        ];

        yield from $queryBuilder->select('*')
            ->from('tx_seal_domain_model_filter')
            ->where(...$where)
            ->orderBy('sorting')
            ->executeQuery()
            ->iterateAssociative();
    }

    protected function addCaclulatedValuesForFilterRows(array $filterRows):array {
        foreach ($filterRows as &$filterRow) {
            if ($filterRow['type'] !== 'tagCondition') {
                continue;
            }
            $configuredTags = $this->tagConfigurationParser->parse((string) ($filterRow['tags'] ?? ''));
            $selectedValues = (array) ($requestData['field_' . $filterRow['uid']] ?? []);

            $filterRow['parsedTags'] = array_map(
                static fn(array $tag): array => [
                    'value' => $tag['value'],
                    'label' => $tag['label'],
                    'count' => $tagFacetCounts[$tag['value']] ?? 0,
                    'selected' => in_array($tag['value'], $selectedValues, true),
                ],
                $configuredTags,
            );
        }
        unset($filterRow);

        foreach ($filterRows as &$filterRow) {
            if ($filterRow['type'] !== 'geoDistanceCondition') {
                continue;
            }
            $configuredRadii = $this->radiusConfigurationParser->parse(
                (string) ($filterRow['radius_steps'] ?? '')
            );
            $filterRow['parsedRadii'] = $configuredRadii;
        }
        unset($filterRow);
        return $filterRows;
    }

}
