<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched right before building a Gally search Request, so a project can
 * override contextual values (e.g. the price group id) that the connector
 * has no reliable way to infer on its own.
 */
final class SearchRequestContextEvent extends Event
{
    private ?string $priceGroupId = null;

    public function getPriceGroupId(): ?string
    {
        return $this->priceGroupId;
    }

    public function setPriceGroupId(?string $priceGroupId): void
    {
        $this->priceGroupId = $priceGroupId;
    }
}
