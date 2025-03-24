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

/**
 * Gally result.
 */
class Result
{
    public function __construct(
        private array $productNumbers,
        private int $totalResultCount,
        private int $currentPage,
        private int $itemPerPage,
        private string $sortField,
        private string $sortDirection,
        private array $aggregations,
    ) {
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
     * @return string[]
     */
    public function getProductNumbers(): array
    {
        return $this->productNumbers;
    }

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
}
