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

namespace Gally\SyliusPlugin\Twig\Component\Product;

use Gally\SyliusPlugin\Config\ConfigManager;
use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Gally\SyliusPlugin\Recommendation\RecommendationFinder;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\TwigHooks\Twig\Component\HookableComponentTrait;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * Displays, for a given product association type, the manually curated ("hard") associated products
 * first, then the Gally-recommended products for the same type. Unlike Sylius' native association
 * component, it is mounted with the type itself (not an existing ProductAssociation row), so it also
 * renders Gally-only recommendations for products that have no "hard" association of that type yet.
 */
#[AsTwigComponent]
class RecommendationComponent
{
    use HookableComponentTrait;

    #[ExposeInTemplate('product_association_type')]
    public ProductAssociationTypeInterface $productAssociationType;

    public ProductInterface $product;

    public function __construct(
        private ChannelContextInterface $channelContext,
        private ConfigManager $configManager,
        private RecommendationFinder $recommendationFinder,
    ) {
    }

    /**
     * @return ProductInterface[]
     */
    #[ExposeInTemplate('associated_products')]
    public function associatedProducts(): array
    {
        /** @var GallyChannelInterface $channel */
        $channel = $this->channelContext->getChannel();

        return $this->recommendationFinder->find(
            [$this->product],
            $this->productAssociationType,
            $channel,
            $channel->getGallyProductRecommendationMaxSize(),
        );
    }
}
