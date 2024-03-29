<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Stephan Hochdörfer <S.Hochdoerfer@bitexpert.de>, Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\SyliusPlugin\Service;

use Gally\Rest\Api\IndexApi;
use Gally\Rest\Api\IndexDocumentApi;
use Gally\Rest\Model\IndexCreate;
use Gally\Rest\Model\IndexDetails;
use Gally\Rest\Model\LocalizedCatalog;
use Gally\SyliusPlugin\Api\RestClient;
use Gally\SyliusPlugin\Synchronizer\LocalizedCatalogSynchronizer;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

/**
 * Indexer manager service.
 */
class IndexOperation
{
    public function __construct(
        private RestClient $client,
        private LocalizedCatalogSynchronizer $localizedCatalogSynchronizer
    ) {
    }

    public function createIndex(string $entityType, ChannelInterface $channel, LocaleInterface $locale): string
    {
        /** @var LocalizedCatalog $localizedCatalog */
        $localizedCatalog = $this->localizedCatalogSynchronizer->getByIdentity(
            $channel->getCode() . $locale->getId()
        );
        $indexData = [
            'entityType' => $entityType,
            'localizedCatalog' => $channel->getCode() . '_' . $locale->getCode(),
        ];

        /** @var IndexCreate $index */
        $index = $this->client->query(IndexApi::class, 'postIndexCollection', $indexData);

        return $index->getName();
    }

    public function getIndexByName(string $entityType, ChannelInterface $channel, LocaleInterface $locale): string
    {
        /** @var LocalizedCatalog $localizedCatalog */
        $localizedCatalog = $this->localizedCatalogSynchronizer->getByIdentity(
            $channel->getCode() . '_' . $locale->getCode()
        );

        $indices = $this->client->query(IndexApi::class, 'getIndexCollection');

        /** @var IndexDetails $index */
        foreach ($indices as $index) {
            if ($index->getEntityType() === $entityType
                && $index->getLocalizedCatalog() === '/localized_catalogs/' . $localizedCatalog->getId()
                && 'live' === $index->getStatus()
            ) {
                return $index->getName();
            }
        }

        throw new \LogicException(sprintf('Index for entity %s and localizedCatalog %s does not exist yet. Make sure everything is reindexed.', $entityType, $localizedCatalog->getCode()));
    }

    public function refreshIndex(string $indexName): void
    {
        $this->client->query(IndexApi::class, 'refreshIndexItem', $indexName, []);
    }

    public function installIndex(string $indexName): void
    {
        $this->client->query(IndexApi::class, 'installIndexItem', $indexName, []);
    }

    public function executeBulk(string $indexName, array $documents): mixed
    {
        return $this->client->query(
            IndexDocumentApi::class,
            'postIndexDocumentCollection',
            ['indexName' => $indexName, 'documents' => $documents]
        );
    }
}
