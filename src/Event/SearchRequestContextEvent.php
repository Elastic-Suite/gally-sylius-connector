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
