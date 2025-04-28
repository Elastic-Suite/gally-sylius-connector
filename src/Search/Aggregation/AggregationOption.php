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
 * Gally aggregation option.
 */
class AggregationOption
{
    public function __construct(
        private string $label,
        private string $value,
        private int $count,
    ) {
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getTranslated(): array
    {
        return ['name' => $this->getLabel()];
    }

    public function getId(): string
    {
        return $this->value;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
