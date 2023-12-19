<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Repository;

use Gally\SyliusPlugin\Entity\GallyConfiguration;
use RuntimeException;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\ResourceRepositoryTrait;

final class GallyConfigurationRepository extends EntityRepository implements GallyConfigurationRepositoryInterface
{
    use ResourceRepositoryTrait;

    public function getConfiguration(): GallyConfiguration
    {
        $gallyConfig = $this->createQueryBuilder('o')
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $gallyConfig) {
            $gallyConfig = new GallyConfiguration();
        }

        return $gallyConfig;
    }
}
