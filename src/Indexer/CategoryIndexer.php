<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Indexer;

use Gally\SyliusPlugin\Service\IndexOperation;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonTranslationInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;

class CategoryIndexer extends AbstractIndexer
{
    public function __construct(
        RepositoryInterface $channelRepository,
        IndexOperation $indexOperation,
        private TaxonRepositoryInterface $taxonRepository)
    {
        parent::__construct($channelRepository, $indexOperation);
    }

    public function getEntityType(): string
    {
        return 'category';
    }

    public function getDocumentsToIndex(ChannelInterface $channel, LocaleInterface $locale, array $documentIdsToReindex): iterable
    {
        $menuTaxon = $channel->getMenuTaxon();
        $taxons = $this->taxonRepository->createQueryBuilder('o')
            ->where('o.root = :taxon_id')
            ->andWhere('o.left > :taxon_left')
            ->orderBy('o.left', 'ASC')
            ->getQuery()
            ->execute([
                'taxon_id' => $menuTaxon->getId(),
                'taxon_left' => $menuTaxon->getLeft()
            ]);

        foreach ($taxons as $taxon) {
            /** @var TaxonInterface $taxon */
            $taxonTranslation = $taxon->getTranslation($locale->getCode());

            yield $this->formatTaxon($taxon, $taxonTranslation);
        }
    }

    private function formatTaxon(TaxonInterface $taxon, TaxonTranslationInterface $translation): array
    {
        return [
            'id' => $taxon->getCode(),
            'parentId' => $taxon->getParent()->getCode(),
            'level' => $taxon->getLevel(),
            'path' => $taxon->getSlug(),
            'name' => $translation->getName(),
        ];
    }
}
