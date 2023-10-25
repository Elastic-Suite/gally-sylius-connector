<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Service;

use Gally\Rest\Api\IndexApi;
use Gally\Rest\Api\IndexDocumentApi;
use Gally\Rest\ApiException;
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
            $channel->getId() . $locale->getId()
        );
        $indexData = [
            'entityType' => $entityType,
            'localizedCatalog' =>  $channel->getId() . '_' . $locale->getCode(),
        ];

        /** @var IndexCreate $index */
        $index = $this->client->query(IndexApi::class, 'postIndexCollection', $indexData);

        return $index->getName();
    }

    public function getIndexByName(string $entityType, ChannelInterface $channel, LocaleInterface $locale): string
    {
        /** @var LocalizedCatalog $localizedCatalog */
        $localizedCatalog = $this->localizedCatalogSynchronizer->getByIdentity(
            $channel->getId() . '_' . $locale->getCode()
        );

        $indices = $this->client->query(IndexApi::class, 'getIndexCollection');

        /** @var IndexDetails $index */
        foreach ($indices as $index) {
            if (
                $index->getEntityType() === $entityType
                && $index->getLocalizedCatalog() === '/localized_catalogs/' . $localizedCatalog->getId()
                && $index->getStatus() === 'live'
            ) {
                return $index->getName();
            }
        }

        throw new \LogicException(
            "Index for entity {$entityType} and localizedCatalog {$localizedCatalog->getCode()} does not exist yet. Make sure everything is reindexed."
        );
    }

    public function refreshIndex(string $indexName)
    {
        $this->client->query(IndexApi::class, 'refreshIndexItem', $indexName, []);
    }

    public function installIndex(string $indexName)
    {
        $this->client->query(IndexApi::class, 'installIndexItem', $indexName, []);
    }

    public function executeBulk(string $indexName, array $documents)
    {
        return $this->client->query(
            IndexDocumentApi::class,
            'postIndexDocumentCollection',
            ['indexName' => $indexName, 'documents' => $documents]
        );
    }
}
