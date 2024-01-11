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

namespace Gally\SyliusPlugin\Repository;

use Gally\SyliusPlugin\Entity\GallyConfiguration;
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
