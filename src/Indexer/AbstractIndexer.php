<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Indexer;

use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Gally\SyliusPlugin\Service\IndexOperation;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * Abstract pagination and bulk mechanism to index entity data to Gally.
 */
abstract class AbstractIndexer
{
    public function __construct(
        protected RepositoryInterface $channelRepository,
        protected IndexOperation $indexOperation,
    ) {
    }

    public function reindex(array $documentIdsToReindex = []): void
    {
        /** @var ChannelInterface[] $channels */
        $channels = $this->channelRepository->findAll();

        foreach ($channels as $channel) {
            if (($channel instanceof GallyChannelInterface) && !$channel->getGallyActive()) {
                continue;
            }

            $locales = $channel->getLocales();
            /** @var LocaleInterface $locale */
            foreach ($locales as $locale) {
                if (empty($documentIdsToReindex)) {
                    $indexName = $this->indexOperation->createIndex($this->getEntityType(), $channel, $locale);
                } else {
                    $indexName = $this->indexOperation->getIndexByName($this->getEntityType(), $channel, $locale);
                }

                // @TODO: Make batch size configurable for each entity
                $batchSize = 50;
                $bulk = [];
                foreach ($this->getDocumentsToIndex($channel, $locale, $documentIdsToReindex) as $document) {
                    $bulk[$document['id']] = json_encode($document);
                    if (count($bulk) >= $batchSize) {
                        $this->indexOperation->executeBulk($indexName, $bulk);
                        $bulk = [];
                    }
                }
                if (count($bulk) > 0) {
                    $this->indexOperation->executeBulk($indexName, $bulk);
                }

                if (empty($documentIdsToReindex)) {
                    $this->indexOperation->refreshIndex($indexName);
                    $this->indexOperation->installIndex($indexName);
                }
            }
        }
    }

    abstract public function getEntityType(): string;

    abstract public function getDocumentsToIndex(
        ChannelInterface $channel,
        LocaleInterface $locale,
        array $documentIdsToReindex
    ): iterable;
}
