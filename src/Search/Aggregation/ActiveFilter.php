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

namespace Gally\SyliusPlugin\Search\Aggregation;

/**
 * A currently active facet filter, rendered as a dismissible chip.
 */
final class ActiveFilter
{
    public function __construct(
        private string $label,
        private string $removeUrl,
    ) {
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getRemoveUrl(): string
    {
        return $this->removeUrl;
    }
}
