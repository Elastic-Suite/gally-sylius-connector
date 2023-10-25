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
                $dataSource->restrict($dataSource->getExpressionBuilder()->lessThanOrEqual($field, (int) $value));
            } elseif (str_contains($field, '_boolean')) {
                $field = str_replace('_boolean', '', $field);
                $value = ($value === 'true');
                $dataSource->restrict($dataSource->getExpressionBuilder()->equals($field, $value));
            } elseif (str_contains($field, '_checkbox')) {
                $field = str_replace('_checkbox', '', $field);
                $dataSource->restrict($dataSource->getExpressionBuilder()->equals($field, $value));
            }
        }
    }
}
