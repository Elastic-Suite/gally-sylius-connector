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
    private $pathCache = [];

    public function __construct(
        RepositoryInterface $channelRepository,
        IndexOperation $indexOperation,
        private TaxonRepositoryInterface $taxonRepository
    ) {
        parent::__construct($channelRepository, $indexOperation);
    }

    public function getEntityType(): string
    {
        return 'category';
    }

    public function getDocumentsToIndex(
        ChannelInterface $channel,
        LocaleInterface $locale,
        array $documentIdsToReindex
    ): iterable {
        $taxons = [];

        if (!empty($documentIdsToReindex)) {
            $taxons = $this->taxonRepository->findBy(['id' => $documentIdsToReindex]);
        } else {
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
        }

        foreach ($taxons as $taxon) {
            /** @var TaxonInterface $taxon */
            $taxonTranslation = $taxon->getTranslation($locale->getCode());

            yield $this->formatTaxon($taxon, $taxonTranslation);
        }
    }

    private function formatTaxon(TaxonInterface $taxon, TaxonTranslationInterface $translation): array
    {
        if (isset($this->pathCache[$taxon->getParent()->getId()])) {
            $this->pathCache[$taxon->getId()] = $this->pathCache[$taxon->getParent()->getId()] . '/' . $taxon->getId();
        } else {
            $this->pathCache[$taxon->getId()] = (string) $taxon->getId();
        }

        $parentId = '';
        if ($taxon->getParent()->getLevel() !== 0) {
            $parentId = (string) $taxon->getParent()->getId();
        }

        return [
            'id' => (string) $taxon->getId(),
            'parentId' => $parentId,
            'level' => $taxon->getLevel(),
            'path' => $this->pathCache[$taxon->getId()],
            'name' => $translation->getName(),
        ];
    }
}
