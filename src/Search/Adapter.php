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

use Gally\Rest\ApiException;
use Gally\SyliusPlugin\Api\GraphQlClient;
use Sylius\Component\Channel\Model\ChannelInterface as ChannelModel;
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
        ChannelInterface|ChannelModel $channel,
        TaxonInterface $taxon,
        string $locale,
        array $filters = [],
        array $sorting = [],
        string $search = '',
        int $page = 1,
        int $limit = 9,
    ): Result {
        $data = [
            'requestType' => '' !== $search ? 'product_search' : 'product_catalog',
            'localizedCatalog' => $channel->getCode() . '_' . $locale,
            'currentCategoryId' => str_replace('/', '_', (string) $taxon->getCode()),
            'search' => $search,
            'currentPage' => $page,
            'pageSize' => $limit,
            'filter' => $filters,
        ];

        if ([] !== $sorting) {
            $data['sort'] = $sorting;
        }

        return $this->resultBuilder->build(
            $channel,
            $this->client->query($this->getSearchQuery(), $data),
            $page
        );
    }

    public function viewMoreOption(
        ChannelInterface|ChannelModel $channel,
        ?TaxonInterface $taxon,
        string $locale,
        string $aggregationField,
        array $filters = [],
        string $search = '',
    ): array {
        $response = $this->client->query(
            $this->getViewMoreQuery(),
            [
                'aggregation' => $aggregationField,
                'localizedCatalog' => $channel->getCode() . '_' . $locale,
                'currentCategoryId' => $taxon ? str_replace('/', '_', (string) $taxon->getCode()) : null,
                'search' => $search,
                'filter' => $filters,
            ]
        );
        $data = json_decode($response->getBody()->getContents(), true);
        if (\array_key_exists('errors', $data)) {
            throw new ApiException(reset($data['errors'])['message']);
        }

        return $data['data']['viewMoreProductFacetOptions'];
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

    private function getViewMoreQuery(): string
    {
        return <<<GQL
            query viewMoreProductFacetOptions (
              \$aggregation: String!,
              \$localizedCatalog: String!,
              \$currentCategoryId: String,
              \$search: String,
              \$filter: [ProductFieldFilterInput]
            ) {
              viewMoreProductFacetOptions (
                aggregation: \$aggregation,
                localizedCatalog: \$localizedCatalog,
                currentCategoryId: \$currentCategoryId,
                search: \$search,
                filter: \$filter
              ) {
                value
                label
                count
            }
          }
        GQL;
    }
}
