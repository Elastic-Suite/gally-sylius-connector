<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Search\Aggregation;

/**
 * Gally aggregation
 */
class Aggregation
{
    public function __construct(
        private string $label,
        private string $field,
        private string $type,
        private array $options
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

    public function getOptions(): array
    {
        return $this->options;
    }
}
