<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Repository;

use Gally\SyliusPlugin\Entity\GallyConfiguration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;
final class GallyConfigurationRepository  extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GallyConfiguration::class);
    }
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
