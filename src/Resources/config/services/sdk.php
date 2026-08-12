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

use Gally\Sdk\Client\Client;
use Gally\Sdk\Client\Configuration;
use Gally\Sdk\Repository\CatalogRepository;
use Gally\Sdk\Repository\LocalizedCatalogRepository;
use Gally\Sdk\Service\BundleManager;
use Gally\Sdk\Service\IndexOperation;
use Gally\Sdk\Service\SearchManager;
use Gally\Sdk\Service\StructureSynchonizer;
use Gally\SyliusPlugin\Config\ConfigurationFactory;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;
use Gally\SyliusPlugin\Service\CacheManager;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set(CacheManager::class)
        ->call('setCache', [service('cache.app')]);

    $services->set(Configuration::class)
        ->lazy()
        ->factory([ConfigurationFactory::class, 'create'])
        ->args([service(GallyConfigurationRepository::class)]);

    $services->set(Client::class)
        ->args([service(Configuration::class), service(CacheManager::class)]);

    $services->set(StructureSynchonizer::class)
        ->args([service(Configuration::class), service(CacheManager::class)]);

    $services->set(IndexOperation::class)
        ->args([service(Configuration::class), service(CacheManager::class)]);

    $services->set(BundleManager::class)
        ->args([service(Configuration::class), service(CacheManager::class)]);

    $services->set(SearchManager::class)
        ->args([service(Configuration::class), service(BundleManager::class), service(CacheManager::class)]);

    $services->set(CatalogRepository::class)
        ->args([service(Client::class)]);

    $services->set(LocalizedCatalogRepository::class)
        ->args([service(Client::class), service(CatalogRepository::class)]);
};
