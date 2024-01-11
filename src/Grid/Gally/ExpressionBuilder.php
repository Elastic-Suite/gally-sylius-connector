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

namespace Gally\SyliusPlugin\Grid\Gally;

use Sylius\Component\Grid\Data\ExpressionBuilderInterface;

class ExpressionBuilder implements ExpressionBuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function andX(...$expressions)
    {
        $return = [];

        foreach ($expressions as $filter) {
            foreach ($filter as $key => $value) {
                if (!isset($return[$key])) {
                    $return[$key] = $value;
                } else {
                    $return[$key] += $value;
                }
            }
        }

        return $return;
    }

    /**
     * {@inheritDoc}
     */
    public function orX(...$expressions)
    {
        throw new \RuntimeException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function comparison(string $field, string $operator, $value)
    {
        throw new \RuntimeException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function equals(string $field, $value)
    {
        return [$field => ['eq' => $value]];
    }

    /**
     * {@inheritDoc}
     */
    public function notEquals(string $field, $value)
    {
        return [$field => ['neq' => $value]];
    }

    /**
     * {@inheritDoc}
     */
    public function lessThan(string $field, $value)
    {
        return [$field => ['lt' => $value]];
    }

    /**
     * {@inheritDoc}
     */
    public function lessThanOrEqual(string $field, $value)
    {
        return [$field => ['lte' => $value]];
    }

    /**
     * {@inheritDoc}
     */
    public function greaterThan(string $field, $value)
    {
        return [$field => ['gt' => $value]];
    }

    /**
     * {@inheritDoc}
     */
    public function greaterThanOrEqual(string $field, $value)
    {
        return [$field => ['gte' => $value]];
    }

    /**
     * {@inheritDoc}
     */
    public function in(string $field, array $values)
    {
        return [$field => ['in' => $values]];
    }

    /**
     * {@inheritDoc}
     */
    public function notIn(string $field, array $values)
    {
        throw new \RuntimeException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function isNull(string $field)
    {
        throw new \RuntimeException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function isNotNull(string $field)
    {
        throw new \RuntimeException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function like(string $field, string $pattern)
    {
        if ('translation.name' === $field) {
            return [];
        }

        return [$field => $pattern];
    }

    /**
     * {@inheritDoc}
     */
    public function notLike(string $field, string $pattern)
    {
        throw new \RuntimeException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function orderBy(string $field, string $direction)
    {
        throw new \RuntimeException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function addOrderBy(string $field, string $direction)
    {
        throw new \RuntimeException('Method not implemented');
    }
}
