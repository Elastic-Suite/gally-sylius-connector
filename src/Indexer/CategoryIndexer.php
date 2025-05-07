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

use Gally\Sdk\Service\IndexOperation;
use Gally\SyliusPlugin\Indexer\Provider\CatalogProvider;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Model\TaxonTranslationInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;

/**
 * Class CategoryIndexer.
 *
 * @author    Stephan Hochdörfer <S.Hochdoerfer@bitexpert.de>, Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */
class CategoryIndexer extends AbstractIndexer
{
    /** @var string[] */
    private array $pathCache = [];

    /**
     * @param TaxonRepositoryInterface<TaxonInterface> $taxonRepository
     */
    public function __construct(
        RepositoryInterface $channelRepository,
        CatalogProvider $catalogProvider,
        IndexOperation $indexOperation,
        private TaxonRepositoryInterface $taxonRepository,
    ) {
        parent::__construct($channelRepository, $catalogProvider, $indexOperation);
    }

    public function getEntityType(): string
    {
        return 'category';
    }

    public function getDocumentsToIndex(
        ChannelInterface $channel,
        LocaleInterface $locale,
        array $documentIdsToReindex,
    ): iterable {
        $menuTaxon = $channel->getMenuTaxon();
        if (null === $menuTaxon) {
            return [];
        }

        if (!empty($documentIdsToReindex)) {
            $taxons = [];
            $allTaxons = $this->taxonRepository->findBy(['id' => $documentIdsToReindex]);

            foreach ($allTaxons as $taxon) {
                /** @var TaxonInterface $taxon */
                if (!($taxon->getLeft() >= $menuTaxon->getLeft() && $taxon->getRight() <= $menuTaxon->getRight())) {
                    continue;
                }
                $path = (string) $taxon->getCode();

                $parent = $taxon->getParent();
                while (null !== $parent && $menuTaxon->getId() !== $taxon->getId()) {
                    $path = $parent->getCode() . '/' . $path;
                    if ($parent->getId() === $menuTaxon->getId()) {
                        break;
                    }
                    $parent = $parent->getParent();
                }

                $this->pathCache[$taxon->getCode()] = $path;
                $taxons[] = $taxon;
            }
        } else {
            /** @var iterable<TaxonInterface> $taxons */
            $taxons = $this->taxonRepository->createQueryBuilder('o') /* @phpstan-ignore-line */
                ->andWhere('o.left >= :taxon_left')
                ->andWhere('o.right <= :taxon_right')
                ->orderBy('o.left', 'ASC')
                ->getQuery()
                ->execute([
                    'taxon_left' => $menuTaxon->getLeft(),
                    'taxon_right' => $menuTaxon->getRight(),
                ]);

            foreach ($taxons as $taxon) {
                /** @var TaxonInterface $taxon */
                if ((null !== $taxon->getParent()) && isset($this->pathCache[$taxon->getParent()->getCode()])) {
                    $this->pathCache[$taxon->getCode()] = $this->pathCache[$taxon->getParent()->getCode()] . '/' . $taxon->getCode();
                } else {
                    $this->pathCache[$taxon->getCode()] = (string) $taxon->getCode();
                }
            }
        }

        foreach ($taxons as $taxon) {
            /** @var TaxonInterface $taxon */
            $taxonTranslation = $taxon->getTranslation($locale->getCode());

            yield $this->formatTaxon($taxon, $taxonTranslation, $menuTaxon);
        }
    }

    private function formatTaxon(TaxonInterface $taxon, TaxonTranslationInterface $translation, TaxonInterface $menuTaxon): array
    {
        $parentId = '';
        if (null !== $taxon->getParent() && $menuTaxon->getId() !== $taxon->getId()) {
            $parentId = str_replace('/', '_', (string) $taxon->getParent()->getCode());
        }

        return [
            'id' => str_replace('/', '_', (string) $taxon->getCode()),
            'parentId' => $parentId,
            'level' => $taxon->getLevel() + 1 - $menuTaxon->getLevel(),
            'path' => $this->pathCache[$taxon->getCode()],
            'name' => $translation->getName(),
        ];
    }
}
