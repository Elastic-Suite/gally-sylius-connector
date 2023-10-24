<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Grid;

use Sylius\Component\Grid\Data\ExpressionBuilderInterface;

class ExpressionBuilder implements ExpressionBuilderInterface
{
    /**
     * @inheritDoc
     */
    public function andX(...$expressions)
    {
        throw new \RuntimeException('Method not implemented');
    }

    /**
     * @inheritDoc
     */
    public function orX(...$expressions)
    {
        throw new \RuntimeException('Method not implemented');
    }

    /**
     * @inheritDoc
     */
    public function comparison(string $field, string $operator, $value)
    {
        throw new \RuntimeException('Method not implemented');
    }

    /**
     * @inheritDoc
     */
    public function equals(string $field, $value)
    {
        return [$field => ['eq' => $value]];
    }

    /**
     * @inheritDoc
     */
    public function notEquals(string $field, $value)
    {
        return [$field => ['neq' => $value]];
    }

    /**
     * @inheritDoc
     */
    public function lessThan(string $field, $value)
    {
        return [$field => ['lt' => $value]];
    }

    /**
     * @inheritDoc
     */
    public function lessThanOrEqual(string $field, $value)
    {
        return [$field => ['lte' => $value]];
    }

    /**
     * @inheritDoc
     */
    public function greaterThan(string $field, $value)
    {
        return [$field => ['gt' => $value]];
    }

    /**
     * @inheritDoc
     */
    public function greaterThanOrEqual(string $field, $value)
    {
        return [$field => ['gte' => $value]];
    }

    /**
     * @inheritDoc
     */
    public function in(string $field, array $values)
    {
        throw new \RuntimeException('Method not implemented');
    }

    /**
     * @inheritDoc
     */
    public function notIn(string $field, array $values)
    {
        throw new \RuntimeException('Method not implemented');
    }

    /**
     * @inheritDoc
     */
    public function isNull(string $field)
    {
        throw new \RuntimeException('Method not implemented');
    }

    /**
     * @inheritDoc
     */
    public function isNotNull(string $field)
    {
        throw new \RuntimeException('Method not implemented');
    }

    /**
     * @inheritDoc
     */
    public function like(string $field, string $pattern)
    {
        if ($field === 'translation.name') {
            return [];
        }

        return [$field => $pattern];
    }

    /**
     * @inheritDoc
     */
    public function notLike(string $field, string $pattern)
    {
        throw new \RuntimeException('Method not implemented');
    }

    /**
     * @inheritDoc
     */
    public function orderBy(string $field, string $direction)
    {
        throw new \RuntimeException('Method not implemented');
    }

    /**
     * @inheritDoc
     */
    public function addOrderBy(string $field, string $direction)
    {
        throw new \RuntimeException('Method not implemented');
    }
}
