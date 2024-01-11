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
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface GallyConfigurationRepositoryInterface extends RepositoryInterface
{
    public function getConfiguration(): GallyConfiguration;
}
