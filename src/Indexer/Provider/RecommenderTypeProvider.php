<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2026-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\SyliusPlugin\Indexer\Provider;

use Gally\Sdk\Entity\RecommenderType;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * Gally recommender type data provider, built from Sylius product association types.
 */
class RecommenderTypeProvider implements ProviderInterface
{
    public function __construct(
        private RepositoryInterface $productAssociationTypeRepository,
    ) {
    }

    /**
     * @return iterable<RecommenderType>
     */
    public function provide(): iterable
    {
        /** @var ProductAssociationTypeInterface $productAssociationType */
        foreach ($this->productAssociationTypeRepository->findAll() as $productAssociationType) {
            yield $this->buildRecommenderType($productAssociationType);
        }
    }

    public function buildRecommenderType(ProductAssociationTypeInterface $productAssociationType): RecommenderType
    {
        return new RecommenderType(
            (string) $productAssociationType->getCode(),
            (string) $productAssociationType->getName(),
        );
    }
}
