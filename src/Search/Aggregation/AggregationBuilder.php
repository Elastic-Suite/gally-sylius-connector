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
 * Build aggregation object from gally raw response.
 */
class AggregationBuilder
{
    /**
     * @param array<array<string, array<string, array<string, string>>|string|bool>> $rawAggregationData
     * @return array<Aggregation>
     */
    public static function build(array $rawAggregationData): array
    {
        $aggregationCollection = [];

        foreach ($rawAggregationData as $data) {
            if (isset($data['count'])) {
                $buckets = [];

                if (is_iterable($data['options'])) {
                    foreach ($data['options'] as $bucket) {
                        $buckets[] = new AggregationOption($bucket['label'], $bucket['value'], (int) $bucket['count']);
                    }
                }

                if (is_string($data['label']) && is_string($data['field']) && is_string($data['type']) && (is_string($data['hasMore']) || is_bool($data['hasMore']))) {
                    $aggregationCollection[] = new Aggregation(
                        $data['label'],
                        $data['field'],
                        $data['type'],
                        $buckets,
                        (bool) $data['hasMore']
                    );
                }
            }
        }

        return $aggregationCollection;
    }
}
