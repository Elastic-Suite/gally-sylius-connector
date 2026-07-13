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

namespace Gally\SyliusPlugin\Recommendation;

use Gally\Sdk\Service\RecommenderManager;
use Gally\SyliusPlugin\Indexer\Provider\CatalogProvider;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Repository\ProductAssociationRepositoryInterface;
use Sylius\Component\Product\Model\ProductAssociationInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * For a given association type and a set of context products (fiche produit: 1, panier: N),
 * resolves the manually curated ("hard") directly associated products first, then the
 * Gally-recommended products, ready to be displayed together.
 */
class RecommendationFinder
{
    /**
     * @param ProductAssociationRepositoryInterface<ProductAssociationInterface> $productAssociationRepository
     */
    public function __construct(
        private RecommenderManager $recommenderManager,
        private CatalogProvider $catalogProvider,
        private RepositoryInterface $productRepository,
        private ProductAssociationRepositoryInterface $productAssociationRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Direct associations first, then Gally recommendations, capped at $maxTotal total.
     * $contextProducts themselves are never included. Falls back to direct associations alone if
     * the Gally call fails (logged, never thrown). Callers are expected to only call this when
     * Gally is enabled for $channel (see findDirectAssociations() for the disabled case).
     *
     * @param ProductInterface[] $contextProducts
     *
     * @return ProductInterface[]
     */
    public function find(
        array $contextProducts,
        ProductAssociationTypeInterface $type,
        ChannelInterface $channel,
        int $maxTotal,
    ): array {
        if ($maxTotal <= 0) {
            return [];
        }

        $contextCodes = [];
        foreach ($contextProducts as $product) {
            $contextCodes[] = (string) $product->getCode();
        }

        /** @var array<string, ProductInterface> $recommendedProducts */
        $recommendedProducts = [];
        foreach ($contextProducts as $product) {
            foreach ($this->findDirectAssociations($product, $type, $channel) as $associatedProduct) {
                if (\count($recommendedProducts) >= $maxTotal) {
                    return array_values($recommendedProducts);
                }

                $code = (string) $associatedProduct->getCode();
                if (!\in_array($code, $contextCodes, true) && !isset($recommendedProducts[$code])) {
                    $recommendedProducts[$code] = $associatedProduct;
                }
            }
        }

        try {
            $gallyRecommendations = $this->findGallyRecommendations((string) $type->getCode(), $contextCodes, $maxTotal);
        } catch (\Throwable $exception) {
            $this->logger->warning(sprintf('Gally: unable to load product recommendations: %s', $exception->getMessage()));

            return array_values($recommendedProducts);
        }

        foreach ($gallyRecommendations as $associatedProduct) {
            if (\count($recommendedProducts) >= $maxTotal) {
                break;
            }

            $code = (string) $associatedProduct->getCode();
            if (!\in_array($code, $contextCodes, true) && !isset($recommendedProducts[$code])) {
                $recommendedProducts[$code] = $associatedProduct;
            }
        }

        return array_values($recommendedProducts);
    }

    /**
     * The manually curated products directly associated with $product for $type, scoped
     * to products enabled within $channel.
     *
     * @return ProductInterface[]
     */
    public function findDirectAssociations(
        ProductInterface $product,
        ProductAssociationTypeInterface $type,
        ChannelInterface $channel,
    ): array {
        $productAssociation = $this->productAssociationRepository->findOneBy(['owner' => $product, 'type' => $type]);
        if (null === $productAssociation) {
            return [];
        }

        /** @var int $associationId */
        $associationId = $productAssociation->getId();
        $productAssociation = $this->productAssociationRepository->findWithProductsWithinChannel($associationId, $channel);

        return $productAssociation->getAssociatedProducts()->toArray();
    }

    /**
     * @param string[] $sourceProductCodes products used as the recommendation source (fiche produit: 1, panier: N)
     *
     * @return ProductInterface[] ordered by Gally relevance
     */
    private function findGallyRecommendations(
        string $typeCode,
        array $sourceProductCodes,
        int $count,
    ): array {
        $localizedCatalog = $this->catalogProvider->getLocalizedCatalog();

        $recommendations = $this->recommenderManager->getProductRecommendations(
            $typeCode,
            $localizedCatalog,
            $sourceProductCodes,
            $count,
        );

        $skus = [];
        foreach ($recommendations as $recommendation) {
            /** @var string $sku */
            $sku = $recommendation['sku'];
            $skus[] = $sku;
        }

        if ([] === $skus) {
            return [];
        }

        /** @var ProductInterface[] $products */
        $products = $this->productRepository->findBy(['code' => $skus]);

        $productsByCode = [];
        foreach ($products as $product) {
            $productsByCode[(string) $product->getCode()] = $product;
        }

        $orderedProducts = [];
        foreach ($skus as $sku) {
            if (isset($productsByCode[$sku])) {
                $orderedProducts[] = $productsByCode[$sku];
            }
        }

        return $orderedProducts;
    }
}
