<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Search;

use Gally\Rest\ApiException;
use Gally\SyliusPlugin\Search\Aggregation\AggregationBuilder;
use Psr\Http\Message\ResponseInterface;
use Sylius\Component\Core\Model\ChannelInterface;

/**
 * Build result object from gally raw response.
 */
class ResultBuilder
{
    public function __construct(private AggregationBuilder $aggregationBuilder)
    {
    }

    public function build(ChannelInterface $channel, ?ResponseInterface $response, int $currentPage): Result
    {
        $response = $response ? json_decode($response->getBody()->getContents(), true) : null;

        $this->validate($response);
        $response = $response['data']['products'];

        $productNumbers = [];
        foreach ($response['collection'] as $productRawData) {
            $productNumbers[$productRawData['sku']] = $productRawData['source']['children.sku'] ?? [];
        }

        return new Result(
            $productNumbers,
            (int) $response['paginationInfo']['totalCount'],
            $currentPage,
            (int) $response['paginationInfo']['itemsPerPage'],
            $response['sortInfo']['current'][0]['field'],
            $response['sortInfo']['current'][0]['direction'],
            $this->aggregationBuilder->build($response['aggregations'] ?? [], $channel)
        );
    }

    private function validate(array $response)
    {
        if (array_key_exists('errors', $response)) {
            $firstError = reset($response['errors']);
            throw new ApiException($firstError['debugMessage'] ?? $firstError['message']);
        }

        if (!array_key_exists('data', $response) || !array_key_exists('products', $response['data'])) {
            throw new ApiException('Empty gally response.');
        }

        $data = $response['data']['products'];

        if (
            !array_key_exists('collection', $data)
            || !array_key_exists('paginationInfo', $data)
            || !array_key_exists('sortInfo', $data)
            || !array_key_exists('aggregations', $data)
        ) {
            throw new ApiException('Malformed gally response.');
        }
    }
}
