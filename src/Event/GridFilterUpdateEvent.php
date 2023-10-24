<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Event;

use Gally\SyliusPlugin\Search\Result;
use Symfony\Contracts\EventDispatcher\Event;

final class GridFilterUpdateEvent extends Event
{
    public function __construct(private Result $gallyResult)
    {
    }

    public function getAggregations(): array
    {
        return $this->gallyResult->getAggregations();
    }
}
