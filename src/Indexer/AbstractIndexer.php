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

                /** @var GallyChannelInterface $channel */
                $batchSize = $this->getBatchSize($this->getEntityType(), $channel);
                $bulk = [];
                foreach ($this->getDocumentsToIndex($channel, $locale, $documentIdsToReindex) as $document) {
                    $bulk[$document['id']] = json_encode($document);
                    if (\count($bulk) >= $batchSize) {
                        $this->indexOperation->executeBulk($indexName, $bulk);
                        $bulk = [];
                    }
                }
                if (\count($bulk) > 0) {
                    $this->indexOperation->executeBulk($indexName, $bulk);
                }

                if (empty($documentIdsToReindex)) {
                    $this->indexOperation->refreshIndex($indexName);
                    $this->indexOperation->installIndex($indexName);
                }
            }
        }
    }

    private function getBatchSize(string $entityType, GallyChannelInterface $channel)
    {
        switch ($entityType) {
            case 'category':
                return $channel->getGallyCategoryIndexBatchSize();
            case 'product':
                return $channel->getGallyProductIndexBatchSize();
            default:
                return 50;
        }
    }

    abstract public function getEntityType(): string;

    abstract public function getDocumentsToIndex(
        ChannelInterface $channel,
        LocaleInterface $locale,
        array $documentIdsToReindex
    ): iterable;
}
