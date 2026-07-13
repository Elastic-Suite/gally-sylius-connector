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

namespace Gally\SyliusPlugin\Twig\Component\Cart;

use Gally\SyliusPlugin\Config\ConfigManager;
use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Gally\SyliusPlugin\Recommendation\RecommendationFinder;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Sylius\Component\Order\Model\OrderItemInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * Displays, for the cart association type configured on the channel, the manually curated ("hard")
 * associated products of the products in the cart first, then the Gally-recommended products.
 */
#[AsTwigComponent]
class RecommendationComponent
{
    public function __construct(
        private CartContextInterface $cartContext,
        private ConfigManager $configManager,
        private RepositoryInterface $productAssociationTypeRepository,
        private RecommendationFinder $recommendationFinder,
    ) {
    }

    /**
     * @return ProductInterface[]
     */
    #[ExposeInTemplate('recommended_products')]
    public function recommendedProducts(): array
    {
        try {
            /** @var OrderInterface $cart */
            $cart = $this->cartContext->getCart();
        } catch (CartNotFoundException) {
            return [];
        }

        $channel = $cart->getChannel();
        if (!$channel instanceof GallyChannelInterface || !$this->configManager->isGallyEnabled($channel)) {
            return [];
        }

        $typeCode = $channel->getGallyCartRecommendationTypeCode();
        if (null === $typeCode || '' === $typeCode) {
            return [];
        }

        /** @var ProductAssociationTypeInterface|null $productAssociationType */
        $productAssociationType = $this->productAssociationTypeRepository->findOneBy(['code' => $typeCode]);
        if (null === $productAssociationType) {
            return [];
        }

        // Most recently added item first, so its direct associations take priority when merging/deduping.
        $items = iterator_to_array($cart->getItems());
        usort($items, static fn (OrderItemInterface $a, OrderItemInterface $b): int => $b->getId() <=> $a->getId());

        $cartProducts = [];
        foreach ($items as $item) {
            $product = $item->getVariant()?->getProduct();
            if (null !== $product && !isset($cartProducts[(string) $product->getCode()])) {
                $cartProducts[(string) $product->getCode()] = $product;
            }
        }

        if ([] === $cartProducts) {
            return [];
        }

        return $this->recommendationFinder->find(
            array_values($cartProducts),
            $productAssociationType,
            $channel,
            $channel->getGallyCartRecommendationMaxSize(),
        );
    }
}
