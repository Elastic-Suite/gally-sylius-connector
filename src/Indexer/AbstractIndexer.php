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

use Gally\Sdk\Entity\LocalizedCatalog;
use Gally\Sdk\Entity\Metadata;
use Gally\Sdk\Service\IndexOperation;
use Gally\SyliusPlugin\Indexer\Provider\CatalogProvider;
use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * Abstract pagination and bulk mechanism to index entity data to Gally.
 */
abstract class AbstractIndexer
{
    /** @var LocalizedCatalog[][] */
    private array $localizedCatalogByChannelByLocale;

    public function __construct(
        protected RepositoryInterface $channelRepository,
        protected CatalogProvider $catalogProvider,
        protected IndexOperation $indexOperation,
    ) {
    }

    public function reindex(array $documentIdsToReindex = []): void
    {
        /** @var ChannelInterface[] $channels */
        $channels = $this->channelRepository->findAll();
        $metadata = new Metadata($this->getEntityType());

        foreach ($channels as $channel) {
            if (($channel instanceof GallyChannelInterface) && $channel->getGallyActive()) {
                /** @var LocaleInterface $locale */
                foreach ($channel->getLocales() as $locale) {
                    $localizedCatalog = $this->getLocalizedCatalogByChannelAndLocale($channel, $locale);
                    if (!$localizedCatalog) {
                        throw new \InvalidArgumentException('No localized catalog found for channel ' . $channel->getCode() . ' and locale ' . $locale->getCode() . '. Try to synchronize your structure.');
                    }

                    if (empty($documentIdsToReindex)) {
                        $index = $this->indexOperation->createIndex($metadata, $localizedCatalog);
                    } else {
                        $index = $this->indexOperation->getIndexByName($metadata, $localizedCatalog);
                    }

                    $batchSize = $this->getBatchSize($this->getEntityType(), $channel);
                    $bulk = [];
                    /** @var array $document */
                    foreach ($this->getDocumentsToIndex($channel, $locale, $documentIdsToReindex) as $document) {
                        if (0 === \count($document)) {
                            continue;
                        }
                        /* @phpstan-ignore-next-line */
                        $bulk[$document['id']] = json_encode($document);
                        if (\count($bulk) >= $batchSize) {
                            $this->indexOperation->executeBulk($index, $bulk);
                            $bulk = [];
                        }
                    }
                    if (\count($bulk)) {
                        $this->indexOperation->executeBulk($index, $bulk);
                    }

                    if (empty($documentIdsToReindex)) {
                        $this->indexOperation->refreshIndex($index);
                        $this->indexOperation->installIndex($index);
                    }
                }
            }
        }
    }

    private function getLocalizedCatalogByChannelAndLocale(ChannelInterface $channel, LocaleInterface $locale): ?LocalizedCatalog
    {
        if (!isset($this->localizedCatalogByChannelByLocale)) {
            foreach ($this->catalogProvider->provide() as $localizedCatalog) {
                $catalogCode = $localizedCatalog->getCatalog()->getCode();
                if (!isset($this->localizedCatalogByChannelByLocale[$catalogCode])) {
                    $this->localizedCatalogByChannelByLocale[$catalogCode] = [];
                }
                $this->localizedCatalogByChannelByLocale[$catalogCode][$localizedCatalog->getLocale()] = $localizedCatalog;
            }
        }

        return $this->localizedCatalogByChannelByLocale[$channel->getCode()][$locale->getCode()] ?? null;
    }

    private function getBatchSize(string $entityType, GallyChannelInterface $channel): int
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
        array $documentIdsToReindex,
    ): iterable;
}
