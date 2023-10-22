<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Search\Aggregation;

use Sylius\Component\Core\Model\ChannelInterface;

/**
 * Build aggregation object from gally raw response.
 */
class AggregationBuilder
{
    private const SHOW_MORE_OPTION = 'gally-show-more';

    public function build(array $rawAggregationData, ChannelInterface $channel): array
    {
        $aggregationCollection = [];

        foreach ($rawAggregationData as $data) {
            if ($data['count']) {
                $buckets = [];

                foreach ($data['options'] as $bucket) {
                    $buckets[] = new AggregationOption($bucket['label'], $bucket['value'], (int)$bucket['count']);
                }

                if ($data['hasMore']) {
                    $buckets[] = new AggregationOption(self::SHOW_MORE_OPTION, self::SHOW_MORE_OPTION, 0);
                }

                $aggregationCollection[] = new Aggregation($data['label'], $data['field'], $data['type'], $buckets);
            }
        }

        return $aggregationCollection;
    }
}
