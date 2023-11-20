<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Indexer;

use Gally\SyliusPlugin\Service\IndexOperation;
use Sylius\Component\Core\Calculator\ProductVariantPricesCalculatorInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

class ProductIndexer extends AbstractIndexer
{
    public function __construct(
        RepositoryInterface $channelRepository,
        IndexOperation $indexOperation,
        private ProductRepositoryInterface $productRepository,
        private ProductVariantPricesCalculatorInterface $productVariantPriceCalculator
    ) {
        parent::__construct($channelRepository, $indexOperation);
    }

    public function getEntityType(): string
    {
        return 'product';
    }

    public function getDocumentsToIndex(
        ChannelInterface $channel,
        LocaleInterface $locale,
        array $documentIdsToReindex
    ) : iterable {
        $products = [];

        if (!empty($documentIdsToReindex)) {
            $products = $this->productRepository->findBy(['id' => $documentIdsToReindex]);
        } else {
            $queryBuilder = $this->productRepository->createShopListQueryBuilder(
                $channel,
                $channel->getMenuTaxon(),
                $locale->getCode(),
                [],
                true
            );
            $products = $queryBuilder->getQuery()->execute();
        }

        foreach ($products as $product) {
            /** @var ProductInterface $product */
            yield $this->formatProduct($product, $channel, $locale);
        }
    }

    private function formatProduct(ProductInterface $product, ChannelInterface $channel, LocaleInterface $locale): array
    {
        $variants = $product->getVariants();
        /** @var ProductVariantInterface $variant */
        $variant = $variants->first();

        $data = [
            'id' => "{$product->getId()}",
            'sku' => [$product->getCode()],
            'name' => [$product->getTranslation($locale->getCode())->getName()],
            'image' => [$this->formatMedia($product) ?: null],
            'price' => $this->formatPrice($variant, $channel),
            'stock' => [
                'status' => (bool) $variant->isTracked(),
                'qty' => $variant->getOnHand()
            ],
            'category' => $this->formatCategories($product),
            'free_shipping' => true, // $product->getShippingFree(),
            'rating_avg' => $product->getAverageRating(),
        ];

        foreach ($product->getAttributes() as $attributeValue) {
            $attribute = $attributeValue->getAttribute();
            $data[$attribute->getCode()] = $attributeValue->getValue();
        }

        while ($variants->next()) {
            /** @var ProductVariantInterface $variant */
            $variantData = $this->formatVariant($variants->current(), $channel, $locale);
            $variantData['children.sku'] = $variantData['sku'];
            unset($variantData['sku']);
            foreach ($variantData as $field => $value) {
                if (!isset($data[$field])) {
                    $data[$field] = [];
                }

                $data[$field][] = $value;
            }
        }

        // Remove empty values
        return array_filter(
            $data,
            fn($item, $key) => in_array($key, ['stock']) || !is_array($item) || !empty(array_filter($item)),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function formatVariant(ProductVariantInterface $variant, ChannelInterface $channel, LocaleInterface $locale): array
    {
        $data = [
            'sku' => [$variant->getCode()],
            'name' => [$variant->getTranslation($locale->getCode())->getName()],
            'image' => [ $this->formatMedia($variant->getProduct()) ?: null],
        ];

        foreach ($variant->getOptionValues() as $optionValue) {
            /** @var ProductOptionValueInterface $optionValue */
            $data[$optionValue->getOption()->getCode()][] = [
                'value' => $optionValue->getValue(),
                'label' => $optionValue->getTranslation($locale->getCode())->getValue(),
            ];
        }

        // Remove empty values
        return array_filter(
            $data,
            fn($item, $key) => in_array($key, ['stock']) || !is_array($item) || !empty(array_filter($item)),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function formatPrice(ProductVariantInterface $variant, ChannelInterface $channel): array
    {
        $context = ['channel' => $channel];
        $price = $this->productVariantPriceCalculator->calculate($variant, $context);
        $originalPrice = $this->productVariantPriceCalculator->calculateOriginal($variant, $context);

        // fix price rendering in Gally
        $price = $price / 100;
        $originalPrice = $originalPrice / 100;

        $prices = [];
        $prices[] = [
            'price' => $price,
            'original_price' => $originalPrice,
            'group_id' => 0,
            'is_discounted' => $price < $originalPrice
        ];

        return $prices;
    }

    private function formatMedia(ProductInterface $product): string
    {
        $image = $product->getImagesByType('thumbnail')->first();
        if ($image === false) {
            $image = $product->getImages()->first();
            if ($image === false) {
                return '';
            }
        }

        return $image->getPath();
    }

    private function formatCategories(ProductInterface $product): array
    {
        $categories = [];

        foreach ($product->getTaxons() as $taxon) {
            if ($taxon->isEnabled()) {
                $categories[$taxon->getCode()] = [
                    'id' => (string) $taxon->getCode(),
                    'category_uid' => (string) $taxon->getCode(),
                    'name' => $taxon->getName(),
                    'is_parent' => $taxon->hasChildren(),
                ];
            }
        }

        return array_values($categories);
    }
}
