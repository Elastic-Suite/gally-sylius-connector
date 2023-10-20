<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Search;

/**
 * Gally result
 */
class Result
{
    public function __construct(
        private array $productNumbers,
        private int $totalResultCount,
        private int $currentPage,
        private int $itemPerPage,
        private string $sortField,
        private string $sortDirection
    ) {
    }

    /**
     * Get count of the total results
     * @return int
     */
    public function getTotalResultCount(): int
    {
        return $this->totalResultCount;
    }

    /**
     * Get product numbers from gally response
     *
     * @return string[]
     */
    public function getProductNumbers(): array
    {
        return $this->productNumbers;
    }
}
