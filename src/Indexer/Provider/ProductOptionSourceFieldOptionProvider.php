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
use Sylius\Component\Product\Model\ProductOption;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * Gally source field option provider for Sylius product option values.
 */
class ProductOptionSourceFieldOptionProvider extends AbstractSourceFieldOptionProvider
{
    public function __construct(
        CatalogProvider $catalogProvider,
        private RepositoryInterface $productOptionRepository,
    ) {
        parent::__construct($catalogProvider);
    }

    /**
     * @return iterable<SourceFieldOption>
     */
    public function provide(): iterable
    {
        $metadata = new Metadata('product');

        /** @var ProductOption $option */
        foreach ($this->productOptionRepository->findAll() as $option) {
            $position = 0;
            /** @var ProductOptionValueInterface $value */
            foreach ($option->getValues() as $value) {
                $sourceField = new SourceField($metadata, (string) $option->getCode(), '', '', []);
                /** @var \Doctrine\Common\Collections\Collection<int, \Sylius\Component\Product\Model\ProductOptionValueTranslation> $translations */
                $translations = $value->getTranslations();
                $firstTranslation = $translations->first();
                /** @var ?string $defaultLabel */
                $defaultLabel = false !== $firstTranslation ? $firstTranslation->getValue() : (string) $value->getCode();

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
}
