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

namespace Gally\SyliusPlugin\Indexer\Provider;

/**
 * Gally data provider interface.
 */
interface ProviderInterface
{
    /**
     * The Gally entity this provider contributes to (e.g. "catalog", "sourceField", "sourceFieldOption").
     * Several providers can share the same entity: their provide() results are merged.
     */
    public function getEntity(): string;

    public function provide(): iterable;
}
