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

namespace Gally\SyliusPlugin\Grid\Filter;

use Gally\SyliusPlugin\Config\ConfigManager;
use Gally\SyliusPlugin\Search\FilterConverter;
use Sylius\Component\Grid\Data\DataSourceInterface;
use Sylius\Component\Grid\Filtering\FilterInterface;

class GallyDynamicFilter implements FilterInterface
{
    public function __construct(
        private FilterConverter $filterConverter,
        private ConfigManager $configManager,
    ) {
    }

    public function apply(DataSourceInterface $dataSource, string $name, $data, array $options): void
    {
        if (!$this->configManager->isGallyEnabled()) {
            return;
        }

        /** @var array<string, mixed> $data */
        foreach ($data as $field => $value) {
            $gallyFilter = $this->filterConverter->convert($field, $value);
            if ($gallyFilter) {
                $dataSource->restrict($gallyFilter);
            }
        }
    }
}
