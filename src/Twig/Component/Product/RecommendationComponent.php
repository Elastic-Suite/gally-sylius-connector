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

namespace Gally\SyliusPlugin\Twig\Component\Product;

use Gally\SyliusPlugin\Config\ConfigManager;
use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Gally\SyliusPlugin\Service\RecommendationFinder;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\TwigHooks\Twig\Component\HookableComponentTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * Displays, for a given product association type, the manually curated ("hard") associated products
 * first, then the Gally-recommended products for the same type. Unlike Sylius' native association
 * component, it is mounted with the type itself (not an existing ProductAssociation row), so it also
 * renders Gally-only recommendations for products that have no "hard" association of that type yet.
 *
 * Rendered with `loading: 'lazy'` from recommendations.html.twig so the Gally call doesn't block the
 * product page's initial render.
 */
#[AsLiveComponent(name: 'gally_shop:product:recommendation', template: '@GallySyliusPlugin/shop/product/show/content/product_listing/recommendation.html.twig')]
class RecommendationComponent
{
    use DefaultActionTrait;
    use HookableComponentTrait;

    #[LiveProp(hydrateWith: 'hydrateProductAssociationType', dehydrateWith: 'dehydrateProductAssociationType')]
    #[ExposeInTemplate('product_association_type')]
    public ProductAssociationTypeInterface $productAssociationType;

    #[LiveProp(hydrateWith: 'hydrateProduct', dehydrateWith: 'dehydrateProduct')]
    public ProductInterface $product;

    public function __construct(
        private ChannelContextInterface $channelContext,
        private ConfigManager $configManager,
        private RecommendationFinder $recommendationFinder,
        private RepositoryInterface $productAssociationTypeRepository,
        private RepositoryInterface $productRepository,
    ) {
    }

    public function dehydrateProductAssociationType(ProductAssociationTypeInterface $productAssociationType): string
    {
        return (string) $productAssociationType->getCode();
    }

    public function hydrateProductAssociationType(string $code): ProductAssociationTypeInterface
    {
        $productAssociationType = $this->productAssociationTypeRepository->findOneBy(['code' => $code]);
        \assert($productAssociationType instanceof ProductAssociationTypeInterface);

        return $productAssociationType;
    }

    public function dehydrateProduct(ProductInterface $product): string
    {
        return (string) $product->getCode();
    }

    public function hydrateProduct(string $code): ProductInterface
    {
        $product = $this->productRepository->findOneBy(['code' => $code]);
        \assert($product instanceof ProductInterface);

        return $product;
    }

    /**
     * @return ProductInterface[]
     */
    #[ExposeInTemplate('associated_products')]
    public function associatedProducts(): array
    {
        $channel = $this->channelContext->getChannel();
        if (!$channel instanceof GallyChannelInterface || !$this->configManager->isGallyEnabled($channel)) {
            return [];
        }

        return $this->recommendationFinder->find(
            [$this->product],
            $this->productAssociationType,
            $channel,
            $channel->getGallyProductRecommendationMaxSize(),
        );
    }
}
