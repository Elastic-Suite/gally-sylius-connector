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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gally\SyliusPlugin\Config\ConfigManager;
use Gally\SyliusPlugin\Indexer\Provider\CatalogProvider;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;
use Gally\SyliusPlugin\Twig\GallyContext;
use Gally\SyliusPlugin\Twig\GallyExtension;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set(GallyExtension::class)
        ->args([service(ConfigManager::class)])
        ->tag('twig.extension');

    $services->set(GallyContext::class)
        ->args([
            service(CatalogProvider::class),
            service(GallyConfigurationRepository::class),
            service('sylius.context.channel'),
            service('router'),
        ]);
};
