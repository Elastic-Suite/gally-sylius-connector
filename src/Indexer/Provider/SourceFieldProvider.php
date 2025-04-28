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

namespace Gally\SyliusPlugin\Indexer\Provider;

use Doctrine\Common\Collections\Collection;
use Gally\Sdk\Entity\Label;
use Gally\Sdk\Entity\LocalizedCatalog;
use Gally\Sdk\Entity\Metadata;
use Gally\Sdk\Entity\SourceField;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Product\Model\ProductAttributeTranslation;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionTranslation;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * Gally Catalog data provider.
 */
class SourceFieldProvider implements ProviderInterface
{
    /** @var LocalizedCatalog[] */
    private array $localizedCatalogs = [];
    private array $metadataCache = [];

    public function __construct(
        protected CatalogProvider $catalogProvider,
        protected RepositoryInterface $productAttributeRepository,
        protected RepositoryInterface $productOptionRepository,
    ) {
        foreach ($this->catalogProvider->provide() as $localizedCatalog) {
            $this->localizedCatalogs[] = $localizedCatalog;
        }
    }

    /**
     * @return iterable<SourceField>
     */
    public function provide(): iterable
    {
        /** @var ProductAttributeInterface $productAttribute */
        foreach ($this->productAttributeRepository->findAll() as $productAttribute) {
            yield $this->buildSourceField('product', $productAttribute);
        }
        /** @var ProductOptionInterface $productOption */
        foreach ($this->productOptionRepository->findAll() as $productOption) {
            yield $this->buildSourceField('product', $productOption, 'select');
        }
    }

    public function buildSourceField(
        string $entity,
        ProductAttributeInterface|ProductOptionInterface $attribute,
        ?string $type = null,
    ): SourceField {
        if (!\array_key_exists($entity, $this->metadataCache)) {
            $this->metadataCache[$entity] = new Metadata($entity);
        }

        /** @var Collection<ProductAttributeTranslation|ProductOptionTranslation> $translations */
        $translations = $attribute->getTranslations();
        $defaultLabel = $translations->first()->getName();

        return new SourceField(
            $this->metadataCache[$entity],
            $attribute->getCode(),
            $type ?: $this->getGallyType($attribute->getType()),
            $defaultLabel,
            $this->getLabels($translations, $defaultLabel),
        );
    }

    /**
     * @param Collection<ProductAttributeTranslation|ProductOptionTranslation> $translations
     */
    private function getLabels(Collection $translations, string $defaultLabel): array
    {
        $labelsByLocal = [];
        foreach ($translations as $translation) {
            $locale = str_replace('-', '_', $translation->getLocale());
            $labelsByLocal[$locale] = $translation->getName();
        }

        $labels = [];
        foreach ($this->localizedCatalogs as $localizedCatalog) {
            $label = $labelsByLocal[$localizedCatalog->getLocale()] ?? null;
            if ($label && $label !== $defaultLabel) {
                $labels[] = new Label($localizedCatalog, $label);
            }
        }

        return $labels;
    }

    public function getGallyType(string $type): string
    {
        switch ($type) {
            case 'integer':
                return 'int';
            case 'percent':
                return 'float';
            case 'date':
                return 'date';
            case 'datetime':
                return 'datetime';
            case 'checkbox':
                return 'boolean';
            case 'select':
                return 'select';
            case 'text':
            case 'textarea':
            default:
                return 'text';
        }
    }
}
