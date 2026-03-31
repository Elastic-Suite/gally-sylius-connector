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

namespace Gally\SyliusPlugin\Search;

use Gally\SyliusPlugin\Search\Aggregation\Aggregation;

/**
 * Gally result.
 */
class Result
{
    /**
     * @param array<string, true> $productNumbers
     * @param Aggregation[]       $aggregations
     * @param array<mixed>        $appliedFilters Raw ExpressionBuilder filters
     */
    public function __construct(
        private array $productNumbers,
        private int $totalResultCount,
        private int $currentPage,
        private int $itemPerPage,
        private string $sortField,
        private string $sortDirection,
        private array $aggregations,
        private array $appliedFilters = [],
        private string $queryText = '',
    ) {
    }

    /**
     * Transform raw ExpressionBuilder filters into tracking format [{name, value}].
     *
     * @param array<mixed> $filters
     *
     * @return array<array{name: string, value: string}>
     */
    public static function buildTrackingFilters(array $filters): array
    {
        $result = [];
        foreach ($filters as $expression) {
            if (!\is_array($expression)) {
                continue;
            }
            foreach ($expression as $field => $operators) {
                if (!\is_string($field) || !\is_array($operators)) {
                    continue;
                }
                if (isset($operators['in'])) {
                    // Checkbox: one entry per selected value
                    foreach ((array) $operators['in'] as $value) {
                        $result[] = ['name' => $field, 'value' => (string) $value];
                    }
                } elseif (isset($operators['eq'])) {
                    // Boolean: true → 1, false → 0
                    $result[] = ['name' => $field, 'value' => $operators['eq'] ? '1' : '0'];
                } elseif (isset($operators['gte']) || isset($operators['lte'])) {
                    // Slider: min-max format
                    $min = isset($operators['gte']) ? (string) $operators['gte'] : '';
                    $max = isset($operators['lte']) ? (string) $operators['lte'] : '';
                    $result[] = ['name' => $field, 'value' => "{$min}-{$max}"];
                }
            }
        }

        return $result;
    }

    /**
     * Get count of the total results.
     */
    public function getTotalResultCount(): int
    {
        return $this->totalResultCount;
    }

    /**
     * Get product numbers from gally response.
     *
     * @return array<string, true>
     */
    public function getProductNumbers(): array
    {
        return $this->productNumbers;
    }

    /**
     * @return Aggregation[]
     */
    public function getAggregations(): array
    {
        return $this->aggregations;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getItemPerPage(): int
    {
        return $this->itemPerPage;
    }

    public function getSortField(): string
    {
        return $this->sortField;
    }

    public function getSortDirection(): string
    {
        return $this->sortDirection;
    }

    /**
     * @return array<array{name: string, value: string}>
     */
    public function getAppliedFilters(): array
    {
        return self::buildTrackingFilters($this->appliedFilters);
    }

    public function getQueryText(): string
    {
        return $this->queryText;
    }
}
