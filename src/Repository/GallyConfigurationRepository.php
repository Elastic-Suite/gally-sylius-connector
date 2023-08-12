<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Repository;

use Gally\SyliusPlugin\Entity\GallyConfiguration;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\ResourceRepositoryTrait;
use RuntimeException;

final class GallyConfigurationRepository extends EntityRepository implements GallyConfigurationRepositoryInterface
{
    use ResourceRepositoryTrait;

    public function getConfiguration(): GallyConfiguration
    {
        $gallyConfig = $this->createQueryBuilder('o')
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $gallyConfig) {
            throw new RuntimeException('There MUST be exactly one Gally_configuration available.');
        }

        return $gallyConfig;
    }
}
