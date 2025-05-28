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
use Gally\Sdk\Entity\SourceFieldOption;
use Sylius\Component\Product\Model\ProductAttribute;
use Sylius\Component\Product\Model\ProductOption;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslation;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * Gally Catalog data provider.
 */
class SourceFieldOptionProvider implements ProviderInterface
{
    /** @var LocalizedCatalog[] */
    private array $localizedCatalogs = [];

    public function __construct(
        private CatalogProvider $catalogProvider,
        private RepositoryInterface $productAttributeRepository,
        private RepositoryInterface $productOptionRepository,
    ) {
        foreach ($this->catalogProvider->provide() as $localizedCatalog) {
            $this->localizedCatalogs[] = $localizedCatalog;
        }
    }

    /**
     * @return iterable<SourceFieldOption>
     */
    public function provide(): iterable
    {
        $metadata = new Metadata('product');

        /** @var ProductAttribute $attribute */
        foreach ($this->productAttributeRepository->findAll() as $attribute) {
            if ('select' === $attribute->getType()) {
                $position = 0;
                $configuration = $attribute->getConfiguration();
                /** @var array<array<string, string>|null> $choices */
                $choices = $configuration['choices'] ?? [];
                foreach ($choices as $code => $choice) {
                    $translations = [];
                    foreach ($choice ?? [] as $locale => $translation) {
                        $translations[] = [
                            'locale' => $locale,
                            'translation' => $translation,
                        ];
                    }
                    $sourceField = new SourceField($metadata, (string) $attribute->getCode(), '', '', []);
                    /** @var ?string $defaultLabel */
                    $defaultLabel = reset($translations)['translation'] ?? $attribute->getCode();

                    yield $this->buildSourceFieldOption(
                        $sourceField,
                        $code,
                        (string) $defaultLabel,
                        $translations,
                        ++$position,
                    );
                }
            }
        }

        /** @var ProductOption $option */
        foreach ($this->productOptionRepository->findAll() as $option) {
            $position = 0;
            /** @var ProductOptionValueInterface $value */
            foreach ($option->getValues() as $value) {
                $sourceField = new SourceField($metadata, (string) $option->getCode(), '', '', []);
                /** @var list<array<string, string>> $translations */
                $translations = $value->getTranslations();
                /** @var ?string $defaultLabel */
                $defaultLabel = reset($translations)['translation'] ?? $value->getCode();

                yield $this->buildSourceFieldOption(
                    $sourceField,
                    (string) $value->getCode(),
                    (string) $defaultLabel,
                    $translations,
                    ++$position,
                );
            }
        }
    }

    /**
     * @param Collection<int, ProductOptionValueTranslation>|list<array<string, string>> $translations
     */
    public function buildSourceFieldOption(
        SourceField $sourceField,
        string $code,
        string $defaultLabel,
        Collection|array $translations,
        int $position,
    ): SourceFieldOption {
        /** @var Label[] $labels */
        $labels = $this->getLabels($translations, $defaultLabel);

        return new SourceFieldOption(
            $sourceField,
            $code,
            $position,
            $defaultLabel,
            $labels,
        );
    }

    /**
     * @param Collection<int, ProductOptionValueTranslation>|list<array<string, string>> $translations
     */
    protected function getLabels(Collection|array $translations, string $defaultLabel): array
    {
        $labelsByLocal = [];
        foreach ($translations as $translation) {
            $locale = str_replace(
                '-',
                '_',
                $translation instanceof ProductOptionValueTranslation
                    ? (string) $translation->getLocale()
                    : $translation['locale']
            );
            $labelsByLocal[$locale] = $translation instanceof ProductOptionValueTranslation
                ? $translation->getValue()
                : $translation['translation'];
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
}
