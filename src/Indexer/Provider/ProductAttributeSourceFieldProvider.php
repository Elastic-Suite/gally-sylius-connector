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
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * Gally source field provider for Sylius product attributes, plus the plugin's static fields.
 */
class ProductAttributeSourceFieldProvider extends AbstractSourceFieldProvider
{
    /** @var Metadata[] */
    private array $metadataCache = [];

    public function __construct(
        CatalogProvider $catalogProvider,
        private RepositoryInterface $productAttributeRepository,
    ) {
        parent::__construct($catalogProvider);
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

        // Static fields are not tied to attributes or options, kept here to avoid a third provider.
        $staticSourceField = [
            'product' => ['slug' => 'text'],
            'category' => ['slug' => 'text'],
        ];
        foreach ($staticSourceField as $entity => $fields) {
            foreach ($fields as $code => $type) {
                if (!\array_key_exists($entity, $this->metadataCache)) {
                    $this->metadataCache[$entity] = new Metadata($entity);
                }

                yield new SourceField($this->metadataCache[$entity], $code, $this->getGallyType($type), $code, []);
            }
        }
    }
}
