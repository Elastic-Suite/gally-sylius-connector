<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Grid\Filter;

use Sylius\Component\Grid\Data\DataSourceInterface;
use Sylius\Component\Grid\Filtering\FilterInterface;

class GallyDynamicFilter implements FilterInterface
{
    public function apply(DataSourceInterface $dataSource, string $name, $data, array $options): void
    {
        foreach ($data as $field => $value) {
            if ($value === '') {
                continue;
            }

            if (str_contains($field, '_slider')) {
                $field = str_replace('_slider', '', $field);
                $values = explode(';', $value, 2);
                $dataSource->restrict($dataSource->getExpressionBuilder()->andX(
                    $dataSource->getExpressionBuilder()->greaterThanOrEqual($field, (int) $values[0]),
                    $dataSource->getExpressionBuilder()->lessThanOrEqual($field, (int) $values[1]),
                ));
            } elseif (str_contains($field, '_boolean')) {
                $field = str_replace('_boolean', '', $field);
                $value = ($value === 'true');
                $dataSource->restrict($dataSource->getExpressionBuilder()->equals($field, $value));
            } else {
                $dataSource->restrict($dataSource->getExpressionBuilder()->equals($field, $value));
            }
        }
    }
}
