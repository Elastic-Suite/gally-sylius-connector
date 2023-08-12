<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Repository;

use Gally\SyliusPlugin\Entity\GallyConfiguration;

interface GallyConfigurationRepositoryInterface
{
    public function getConfiguration(): GallyConfiguration;
}
