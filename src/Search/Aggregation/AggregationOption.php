<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Search\Aggregation;

/**
 * Gally aggregation option
 */
class AggregationOption
{
    public function __construct(
        private string $label,
        private string $value,
        private int $count
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
