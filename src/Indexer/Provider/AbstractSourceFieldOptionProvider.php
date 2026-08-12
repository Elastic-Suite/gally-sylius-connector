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
use Gally\Sdk\Entity\SourceField;
use Gally\Sdk\Entity\SourceFieldOption;
use Sylius\Component\Product\Model\ProductOptionValueTranslation;

/**
 * Shared source field option building logic for the product attribute and product option providers.
 */
abstract class AbstractSourceFieldOptionProvider implements ProviderInterface
{
    /** @var LocalizedCatalog[] */
    private array $localizedCatalogs = [];

    public function __construct(protected CatalogProvider $catalogProvider)
    {
        foreach ($this->catalogProvider->provide() as $localizedCatalog) {
            $this->localizedCatalogs[] = $localizedCatalog;
        }
    }

    public function getEntity(): string
    {
        return 'sourceFieldOption';
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
            if (null !== $label && '' !== $label && $label !== $defaultLabel) {
                $labels[] = new Label($localizedCatalog, $label);
            }
        }

        return $labels;
    }
}
