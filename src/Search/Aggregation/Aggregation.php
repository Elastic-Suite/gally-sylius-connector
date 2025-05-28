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
 * Gally aggregation.
 */
class Aggregation
{
    /**
     * @param AggregationOption[] $options
     */
    public function __construct(
        private string $label,
        private string $field,
        private string $type,
        private array $options,
        private bool $hasMore,
    ) {
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return AggregationOption[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function hasMore(): bool
    {
        return $this->hasMore;
    }
}
