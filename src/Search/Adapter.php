<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Search;

use Gally\SyliusPlugin\Api\GraphQlClient;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\TaxonInterface;

/**
 * Gally search adapter.
 */
class Adapter
{
    public function __construct(
        private GraphQlClient $client,
        private ResultBuilder $resultBuilder
    ) {
    }

    public function search(
        ChannelInterface $channel,
        TaxonInterface $taxon,
        string $locale,
        string $search = '',
        array $sorting = [],
        $page = 1,
        $limit = 9,
    ): Result {
        $data = [
            'requestType' => $search !== '' ? 'product_search' : 'product_catalog',
            'localizedCatalog' => $channel->getId() . '_' . $locale,
            'currentCategoryId' => (string) $taxon->getId(),
            'search' => $search,
            'currentPage' => $page,
            'pageSize' => $limit,
            'filter' => [], //$this->getFiltersFromCriteria($criteria)
            'sort' => $sorting,
        ];

        return $this->resultBuilder->build(
            $channel,
            $this->client->query($this->getSearchQuery(), $data),
            $page
        );
    }

    private function getSearchQuery(): string
    {
        return <<<GQL
            query getProducts (
              \$requestType: ProductRequestTypeEnum!,
              \$localizedCatalog: String!,
              \$currentPage: Int,
              \$currentCategoryId: String,
              \$pageSize: Int,
              \$search: String,
              \$sort: ProductSortInput,
              \$filter: [ProductFieldFilterInput]
            ) {
              products (
                requestType: \$requestType,
                localizedCatalog: \$localizedCatalog,
                currentPage: \$currentPage,
                currentCategoryId: \$currentCategoryId,
                pageSize: \$pageSize,
                search: \$search,
                sort: \$sort,
                filter: \$filter
              ) {
                collection { ... on Product { sku source } }
                paginationInfo { lastPage itemsPerPage totalCount }
                sortInfo { current { field direction } }
                aggregations {
                  type
                  field
                  label
                  count
                  hasMore
                  options { count label value }
                }
            }
          }
        GQL;
    }
}
