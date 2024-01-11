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

namespace Gally\SyliusPlugin\Model;

interface GallyChannelInterface
{
    public function getGallyActive(): bool;

    public function setGallyActive(bool $isGallyActive): void;

    public function getGallyProductIndexBatchSize(): int;

    public function setGallyProductIndexBatchSize(int $gallyProductIndexBatchSize): void;

    public function getGallyCategoryIndexBatchSize(): int;

    public function setGallyCategoryIndexBatchSize(int $gallyCategoryIndexBatchSize): void;
}
