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
