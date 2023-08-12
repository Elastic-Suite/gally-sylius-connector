<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Repository;

use Gally\SyliusPlugin\Entity\GallyConfiguration;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface GallyConfigurationRepositoryInterface extends RepositoryInterface
{
    public function getConfiguration(): GallyConfiguration;
}
