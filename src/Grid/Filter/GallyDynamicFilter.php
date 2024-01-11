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

namespace Gally\SyliusPlugin\Grid\Filter;

use Sylius\Component\Grid\Data\DataSourceInterface;
use Sylius\Component\Grid\Filtering\FilterInterface;

class GallyDynamicFilter implements FilterInterface
{
    public function apply(DataSourceInterface $dataSource, string $name, $data, array $options): void
    {
        foreach ($data as $field => $value) {
            if ('' === $value) {
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
                $value = ('true' === $value);
                $dataSource->restrict($dataSource->getExpressionBuilder()->equals($field, $value));
            } else {
                if (\is_array($value)) {
                    $dataSource->restrict($dataSource->getExpressionBuilder()->in($field, $value));
                } else {
                    $dataSource->restrict($dataSource->getExpressionBuilder()->equals($field, $value));
                }
            }
        }
    }
}
