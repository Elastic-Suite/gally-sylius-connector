<?php

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
