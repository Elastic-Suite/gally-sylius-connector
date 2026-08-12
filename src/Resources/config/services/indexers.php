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

use Gally\Sdk\Repository\LocalizedCatalogRepository;
use Gally\Sdk\Service\IndexOperation;
use Gally\Sdk\Service\StructureSynchonizer;
use Gally\SyliusPlugin\Config\ConfigManager;
use Gally\SyliusPlugin\Indexer\AbstractIndexer;
use Gally\SyliusPlugin\Indexer\CategoryIndexer;
use Gally\SyliusPlugin\Indexer\ProductIndexer;
use Gally\SyliusPlugin\Indexer\Provider\CatalogProvider;
use Gally\SyliusPlugin\Indexer\Provider\SourceFieldOptionProvider;
use Gally\SyliusPlugin\Indexer\Provider\SourceFieldProvider;
use Gally\SyliusPlugin\Indexer\Subscriber\CategorySubscriber;
use Gally\SyliusPlugin\Indexer\Subscriber\ChannelSubscriber;
use Gally\SyliusPlugin\Indexer\Subscriber\ProductAttributeSubscriber;
use Gally\SyliusPlugin\Indexer\Subscriber\ProductSubscriber;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set(CatalogProvider::class)
        ->args([service('sylius.repository.channel'), service('sylius.context.channel'), service('sylius.context.locale')])
        ->tag('gally.dataprovider', ['entity' => 'catalog']);

    $services->set(SourceFieldProvider::class)
        ->args([service(CatalogProvider::class), service('sylius.repository.product_attribute'), service('sylius.repository.product_option')])
        ->tag('gally.dataprovider', ['entity' => 'sourceField']);

    $services->set(SourceFieldOptionProvider::class)
        ->args([service(CatalogProvider::class), service('sylius.repository.product_attribute'), service('sylius.repository.product_option')])
        ->tag('gally.dataprovider', ['entity' => 'sourceFieldOption']);

    $services->set(ChannelSubscriber::class)
        ->args([service(CatalogProvider::class), service(StructureSynchonizer::class), service(ConfigManager::class)])
        ->tag('kernel.event_subscriber');

    $services->set(ProductAttributeSubscriber::class)
        ->args([
            service(LocalizedCatalogRepository::class),
            service(SourceFieldProvider::class),
            service(SourceFieldOptionProvider::class),
            service(StructureSynchonizer::class),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(AbstractIndexer::class)
        ->abstract()
        ->args([service('sylius.repository.channel'), service(CatalogProvider::class), service(IndexOperation::class)]);

    $services->set(CategoryIndexer::class)
        ->parent(AbstractIndexer::class)
        ->args([service('sylius.repository.taxon')])
        ->tag('gally.entity.indexer', ['priority' => 50]);

    $services->set(ProductIndexer::class)
        ->parent(AbstractIndexer::class)
        ->args([service('sylius.repository.product'), service('sylius.calculator.product_variant_price')])
        ->tag('gally.entity.indexer', ['priority' => 50]);

    $services->set(CategorySubscriber::class)
        ->args([service(CategoryIndexer::class)])
        ->tag('kernel.event_subscriber');

    $services->set(ProductSubscriber::class)
        ->args([service(ProductIndexer::class)])
        ->tag('kernel.event_subscriber');
};
