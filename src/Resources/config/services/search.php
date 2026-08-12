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

use Gally\Sdk\Service\SearchManager;
use Gally\SyliusPlugin\Config\ConfigManager;
use Gally\SyliusPlugin\Form\Type\Filter\GallyDynamicFilterType;
use Gally\SyliusPlugin\Grid\DataProvider;
use Gally\SyliusPlugin\Grid\Filter\GallyDynamicFilter;
use Gally\SyliusPlugin\Grid\Gally\Driver;
use Gally\SyliusPlugin\Grid\Gally\Search\SearchDriver;
use Gally\SyliusPlugin\Indexer\Provider\CatalogProvider;
use Gally\SyliusPlugin\Search\FilterConverter;
use Gally\SyliusPlugin\Search\Finder;
use Sylius\Bundle\GridBundle\Form\Type\Filter\SelectFilterType;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set(FilterConverter::class);

    $services->set(Finder::class)
        ->args([service(SearchManager::class), service(CatalogProvider::class)]);

    $services->set(SearchDriver::class)
        ->args([service(SearchManager::class), service(CatalogProvider::class), service('event_dispatcher'), service('doctrine')])
        ->tag('sylius.grid_driver', ['alias' => 'gally/search']);

    $services->set(Driver::class)
        ->args([service('doctrine'), service(CatalogProvider::class), service(SearchManager::class), service('event_dispatcher')])
        ->tag('sylius.grid_driver', ['alias' => 'gally/rest']);

    $services->alias('sylius.grid_driver.gally.rest', Driver::class);

    $services->set(DataProvider::class)
        ->decorate('sylius.grid.data_provider')
        ->args([
            service('.inner'),
            service('sylius.grid.data_source_provider'),
            service('sylius.grid.filters_applicator'),
            service('sylius.context.channel'),
        ]);

    $services->set(GallyDynamicFilter::class)
        ->args([service(FilterConverter::class), service(ConfigManager::class)])
        ->tag('sylius.grid_filter', ['type' => 'gally_dynamic_filter', 'form_type' => GallyDynamicFilterType::class]);

    $services->set(GallyDynamicFilterType::class)
        ->autoconfigure()
        ->args([
            service('router'),
            service('request_stack'),
            service('sylius.repository.taxon'),
            service('sylius.context.locale'),
        ])
        ->tag('kernel.event_listener', ['event' => 'gally.grid.configure_filter', 'method' => 'onFilterUpdate']);

    $services->set('sylius.form.type.grid_filter.select', SelectFilterType::class)
        ->tag('form.type');
};
