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

use Gally\SyliusPlugin\Grid\Gally\ExpressionBuilder;
use Sylius\Component\Grid\Data\ExpressionBuilderInterface;

/**
 * Convert Sylius filter to Gally filter.
 */
class FilterConverter
{
    private ExpressionBuilderInterface $expressionBuilder;

    public function __construct()
    {
        $this->expressionBuilder = new ExpressionBuilder();
    }

    public function convert(string $field, mixed $value): mixed
    {
        if ('' === $value) {
            return null;
        }

        if (str_contains($field, '_slider')) {
            if (!\is_string($value)) {
                return null;
            }

            $field = str_replace('_slider', '', $field);
            $values = explode('|', $value, 2);

            return $this->expressionBuilder->andX(
                $this->expressionBuilder->greaterThanOrEqual($field, (int) $values[0]),
                $this->expressionBuilder->lessThanOrEqual($field, (int) $values[1]),
            );
        }

        if (str_contains($field, '_boolean')) {
            $field = str_replace('_boolean', '', $field);
            $value = ('true' === $value);

            return $this->expressionBuilder->equals($field, $value);
        }

        if (\is_array($value)) {
            return $this->expressionBuilder->in($field, $value);
        }

        return $this->expressionBuilder->equals($field, $value);
    }
}
