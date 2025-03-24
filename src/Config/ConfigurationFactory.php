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

namespace Gally\SyliusPlugin\Config;

use Gally\Sdk\Client\Configuration;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;

class ConfigurationFactory
{
    public static function create(GallyConfigurationRepository $gallyConfigurationRepository): Configuration
    {
        $configuration = $gallyConfigurationRepository->getConfiguration();

        return new Configuration(
            $configuration->getBaseUrl(),
            $configuration->getCheckSSL(),
            $configuration->getUserName(),
            $configuration->getPassword(),
        );
    }
}
