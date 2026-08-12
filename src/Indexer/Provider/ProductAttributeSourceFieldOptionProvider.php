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

use Gally\Sdk\Entity\Metadata;
use Gally\Sdk\Entity\SourceField;
use Gally\Sdk\Entity\SourceFieldOption;
use Sylius\Component\Product\Model\ProductAttribute;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * Gally source field option provider for "select" Sylius product attribute choices.
 */
class ProductAttributeSourceFieldOptionProvider extends AbstractSourceFieldOptionProvider
{
    public function __construct(
        CatalogProvider $catalogProvider,
        private RepositoryInterface $productAttributeRepository,
    ) {
        parent::__construct($catalogProvider);
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
                        (string) $code,
                        (string) $defaultLabel,
                        $translations,
                        ++$position,
                    );
                }
            }
        }
    }
}
