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

use Gally\Sdk\Entity\SourceField;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * Gally source field provider for Sylius product options.
 */
class ProductOptionSourceFieldProvider extends AbstractSourceFieldProvider
{
    public function __construct(
        CatalogProvider $catalogProvider,
        private RepositoryInterface $productOptionRepository,
    ) {
        parent::__construct($catalogProvider);
    }

    /**
     * @return iterable<SourceField>
     */
    public function provide(): iterable
    {
        /** @var ProductOptionInterface $productOption */
        foreach ($this->productOptionRepository->findAll() as $productOption) {
            yield $this->buildSourceField('product', $productOption, 'select');
        }
    }
}
