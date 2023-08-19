<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Model;

interface GallyChannelInterface
{
    public function getGallyActive(): bool;

    public function setGallyActive(bool $isGallyActive): void;
}
