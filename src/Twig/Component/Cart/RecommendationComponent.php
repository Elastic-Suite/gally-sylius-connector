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

namespace Gally\SyliusPlugin\Twig\Component\Cart;

use Gally\SyliusPlugin\Config\ConfigManager;
use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Gally\SyliusPlugin\Service\RecommendationFinder;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Sylius\Component\Order\Model\OrderItemInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * Displays, for a given cart association type, the manually curated ("hard") associated products
 * of the products in the cart first, then the Gally-recommended products.
 */
#[AsTwigComponent]
class RecommendationComponent
{
    private bool $cartResolved = false;

    private ?OrderInterface $cart = null;

    #[ExposeInTemplate('product_association_type')]
    public ProductAssociationTypeInterface $productAssociationType;

    public function __construct(
        private CartContextInterface $cartContext,
        private ConfigManager $configManager,
        private RecommendationFinder $recommendationFinder,
    ) {
    }

    /**
     * @return ProductInterface[]
     */
    #[ExposeInTemplate('recommended_products')]
    public function recommendedProducts(): array
    {
        $cart = $this->getCart();
        if (null === $cart) {
            return [];
        }

        $channel = $cart->getChannel();
        if (!$channel instanceof GallyChannelInterface || !$this->configManager->isGallyEnabled($channel)) {
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
            $this->productAssociationType,
            $channel,
            $this->configManager->getCartRecommendationMaxSize(),
        );
    }

    private function getCart(): ?OrderInterface
    {
        if ($this->cartResolved) {
            return $this->cart;
        }
        $this->cartResolved = true;

        try {
            /** @var OrderInterface $cart */
            $cart = $this->cartContext->getCart();
        } catch (CartNotFoundException) {
            return null;
        }

        return $this->cart = $cart;
    }
}
