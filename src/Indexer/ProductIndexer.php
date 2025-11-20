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
use Sylius\Component\Attribute\Model\AttributeValueInterface;
use Sylius\Component\Core\Calculator\ProductVariantPricesCalculatorInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * Class ProductIndexer.
 *
 * @author    Stephan Hochdörfer <S.Hochdoerfer@bitexpert.de>, Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */
class ProductIndexer extends AbstractIndexer
{
    /**
     * @param ProductRepositoryInterface<ProductInterface> $productRepository
     */
    public function __construct(
        RepositoryInterface $channelRepository,
        CatalogProvider $catalogProvider,
        IndexOperation $indexOperation,
        private ProductRepositoryInterface $productRepository,
        private ProductVariantPricesCalculatorInterface $productVariantPriceCalculator,
    ) {
        parent::__construct($channelRepository, $catalogProvider, $indexOperation);
    }

    public function getEntityType(): string
    {
        return 'product';
    }

    public function getDocumentsToIndex(
        ChannelInterface $channel,
        LocaleInterface $locale,
        array $documentIdsToReindex,
    ): iterable {
        if ([] !== $documentIdsToReindex) {
            $products = $this->productRepository->findBy(['id' => $documentIdsToReindex]);
        } else {
            $taxon = $channel->getMenuTaxon();
            if (null === $taxon) {
                throw new \LogicException('No menu taxon define for channel ' . $channel->getCode());
            }
            /** @var TaxonInterface $taxon */
            $queryBuilder = $this->productRepository->createShopListQueryBuilder(
                $channel,
                $taxon,
                (string) $locale->getCode(),
                [],
                true
            );
            $products = $queryBuilder->getQuery()->execute();
        }
        /** @var iterable $products */
        foreach ($products as $product) {
            /** @var ProductInterface $product */
            if (!$product->isEnabled()) {
                continue;
            }

            yield $this->formatProduct($product, $channel, $locale);
        }
    }

    private function formatProduct(ProductInterface $product, ChannelInterface $channel, LocaleInterface $locale): array
    {
        $variants = $product->getVariants();
        /** @var ProductVariantInterface|false $variant */
        $variant = $variants->first();
        if (false === $variant) {
            return [];
        }

        /** @var int|string $productId */
        $productId = $product->getId();
        $data = [
            'id' => (string) $productId,
            'sku' => [$product->getCode()],
            'name' => [$product->getTranslation($locale->getCode())->getName()],
            'description' => [$product->getTranslation($locale->getCode())->getDescription()],
            'slug' => [$product->getTranslation($locale->getCode())->getSlug()],
            'image' => ['' !== $this->formatMedia($product) ? $this->formatMedia($product) : null],
            'price' => $this->formatPrice($variant, $channel),
            'stock' => [
                'status' => $variant->isInStock(),
                'qty' => $variant->getOnHand(),
            ],
            'category' => $this->formatCategories($product),
            'free_shipping' => true, // $product->getShippingFree(),
            'rating_avg' => $product->getAverageRating(),
        ];

        foreach ($product->getAttributes() as $attributeValue) {
            /** @var AttributeValueInterface $attributeValue */
            $attribute = $attributeValue->getAttribute();
            if ($attributeValue->getLocaleCode() !== $locale->getCode() || null === $attribute?->getCode()) {
                continue;
            }

            $attributeValue = $attributeValue->getValue();
            if ('select' === $attribute->getType()) {
                if (!\is_array($attributeValue)) {
                    $attributeValue = [$attributeValue];
                }

                /** @var array<array<array>> $attributeConfiguration */
                $attributeConfiguration = $attribute->getConfiguration();
                foreach ($attributeValue as $key => $value) {
                    $translations = $attributeConfiguration['choices'][$value] ?? [];
                    $label = $translations[$locale->getCode()] ?? '';

                    $attributeValue[$key] = [
                        'value' => $value,
                        'label' => $label,
                    ];
                }
            }

            $data[$attribute->getCode()] = $attributeValue;
        }

        while ($variants->current()) {
            if ($variants->current()->isEnabled()) {
                /** @var ProductVariantInterface $variant */
                $variant = $variants->current();
                $variantData = $this->formatVariant($variant, $channel, $locale);
                foreach ($variantData as $field => $value) {
                    if (!isset($data[$field])) {
                        $data[$field] = [];
                    }

                    if (\is_array($data[$field])) {
                        $data[$field][] = $value;
                    }
                }
            }

            $variants->next();
        }

        // Remove empty values
        return array_filter(
            $data,
            fn ($item, $key) => \in_array($key, ['stock'], true) || !\is_array($item) || [] !== array_filter($item),
            \ARRAY_FILTER_USE_BOTH
        );
    }

    private function formatVariant(ProductVariantInterface $variant, ChannelInterface $channel, LocaleInterface $locale): array
    {
        /** @var ?ProductInterface $parent */
        $parent = $variant->getProduct();
        $data = [
            'children.sku' => [$variant->getCode()],
            'children.name' => [$variant->getTranslation($locale->getCode())->getName()],
            'childen.image' => [null !== $parent ? $this->formatMedia($parent) : null],
        ];

        foreach ($variant->getOptionValues() as $optionValue) {
            if (null === $optionValue->getOption()) {
                continue;
            }

            /* @var ProductOptionValueInterface $optionValue */
            $data[$optionValue->getOption()->getCode()][] = [
                'value' => $optionValue->getCode(),
                'label' => $optionValue->getTranslation($locale->getCode())->getValue(),
            ];
        }

        // Remove empty values
        return array_filter(
            $data,
            fn ($item, $key) => \in_array($key, ['stock'], true) || [] !== array_filter($item),
            \ARRAY_FILTER_USE_BOTH
        );
    }

    private function formatPrice(ProductVariantInterface $variant, ChannelInterface $channel): array
    {
        $context = ['channel' => $channel];
        $price = $this->productVariantPriceCalculator->calculate($variant, $context);
        $originalPrice = $this->productVariantPriceCalculator->calculateOriginal($variant, $context);

        // fix price rendering in Gally
        $price /= 100;
        $originalPrice /= 100;

        $prices = [];
        $prices[] = [
            'price' => $price,
            'original_price' => $originalPrice,
            'group_id' => 0,
            'is_discounted' => $price < $originalPrice,
        ];

        return $prices;
    }

    private function formatMedia(ProductInterface $product): string
    {
        $image = $product->getImagesByType('thumbnail')->first();
        if (false === $image) {
            $image = $product->getImages()->first();
            if (false === $image) {
                return '';
            }
        }

        return $image->getPath() ?? '';
    }

    private function formatCategories(ProductInterface $product): array
    {
        $categories = [];

        foreach ($product->getTaxons() as $taxon) {
            if ($taxon->isEnabled()) {
                $categories[$taxon->getCode()] = [
                    'id' => str_replace('/', '_', (string) $taxon->getCode()),
                    'category_uid' => str_replace('/', '_', (string) $taxon->getCode()),
                    'name' => $taxon->getName(),
                    'is_parent' => $taxon->hasChildren(),
                ];
            }
        }

        return array_values($categories);
    }
}
