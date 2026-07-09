<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\SyliusPlugin\Twig\Component\Product;

use Gally\SyliusPlugin\Search\ActiveFilterResolver;
use Gally\SyliusPlugin\Search\Aggregation\ActiveFilter;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class ActiveFiltersComponent
{
    public function __construct(
        private ActiveFilterResolver $activeFilterResolver,
    ) {
    }

    /**
     * @return ActiveFilter[]
     */
    #[ExposeInTemplate('active_filters')]
    public function activeFilters(): array
    {
        return $this->activeFilterResolver->resolve();
    }

    #[ExposeInTemplate('clear_all_url')]
    public function clearAllUrl(): ?string
    {
        return $this->activeFilterResolver->resolveClearAllUrl();
    }
}
