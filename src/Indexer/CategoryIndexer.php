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

            foreach ($taxons as $taxon) {
                /** @var TaxonInterface $taxon */
                $path = (string) $taxon->getCode();

                $parent = $taxon->getParent();
                while ($parent !== null) {
                    $path = $parent->getCode(). '/' .$path;
                    $parent = $parent->getParent();
                }

                $this->pathCache[$taxon->getCode()] = $path;
            }
        } else {
            $menuTaxon = $channel->getMenuTaxon();
            $taxons = $this->taxonRepository->createQueryBuilder('o')
                ->where('o.root = :taxon_id')
                ->andWhere('o.left >= :taxon_left')
                ->orderBy('o.left', 'ASC')
                ->getQuery()
                ->execute([
                    'taxon_id' => $menuTaxon->getId(),
                    'taxon_left' => $menuTaxon->getLeft()
                ]);

            foreach ($taxons as $taxon) {
                /** @var TaxonInterface $taxon */
                if (($taxon->getParent() !== null) && isset($this->pathCache[$taxon->getParent()->getCode()])) {
                    $this->pathCache[$taxon->getCode()] = $this->pathCache[$taxon->getParent()->getCode()] . '/' . $taxon->getCode();
                } else {
                    $this->pathCache[$taxon->getCode()] = (string) $taxon->getCode();
                }
            }
        }

        foreach ($taxons as $taxon) {
            /** @var TaxonInterface $taxon */
            $taxonTranslation = $taxon->getTranslation($locale->getCode());

            yield $this->formatTaxon($taxon, $taxonTranslation);
        }
    }

    private function formatTaxon(TaxonInterface $taxon, TaxonTranslationInterface $translation): array
    {
        $parentId = '';
        if ($taxon->getParent() !== null) {
            $parentId = (string) $taxon->getParent()->getCode();
        }

        return [
            'id' => (string) $taxon->getCode(),
            'parentId' => $parentId,
            'level' => $taxon->getLevel() + 1,
            'path' => $this->pathCache[$taxon->getCode()],
            'name' => $translation->getName(),
        ];
    }
}
