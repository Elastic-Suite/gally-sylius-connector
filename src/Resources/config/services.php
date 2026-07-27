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

use Doctrine\ORM\Mapping\ClassMetadata;
use Gally\Sdk\Service\StructureSynchonizer;
use Gally\SyliusPlugin\Config\ConfigManager;
use Gally\SyliusPlugin\Controller\Admin\GallyController;
use Gally\SyliusPlugin\Controller\Shop\SearchController;
use Gally\SyliusPlugin\Entity\GallyConfiguration;
use Gally\SyliusPlugin\Form\Extension\ChannelTypeExtension;
use Gally\SyliusPlugin\Listener\AdminMenuListener;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;
use Gally\SyliusPlugin\Search\Finder;
use Gally\SyliusPlugin\Service\CacheManager;

return static function (ContainerConfigurator $container) {
    $container->import('services/sdk.php');
    $container->import('services/commands.php');
    $container->import('services/indexers.php');
    $container->import('services/search.php');
    $container->import('services/twig.php');
    $container->import('services/twig/component/product.php');
    $container->import('services/twig/component/filter.php');
    $container->import('services/tracking.php');

    $container->parameters()
        ->set('gally.model.configuration', GallyConfiguration::class);

    $services = $container->services();

    $services->set(GallyConfigurationRepository::class)
        ->args([
            service('doctrine.orm.entity_manager'),
            inline_service(ClassMetadata::class)
                ->factory([service('doctrine.orm.entity_manager'), 'getClassMetadata'])
                ->args(['%gally.model.configuration%']),
        ]);

    $services->set(GallyController::class)
        ->args([
            service(GallyConfigurationRepository::class),
            service(StructureSynchonizer::class),
            service(ConfigManager::class),
            tagged_iterator('gally.dataprovider'),
            service('translator'),
            service(CacheManager::class),
        ])
        ->call('setContainer', [service('service_container')])
        ->tag('controller.service_arguments');

    $services->set(SearchController::class)
        ->args([service(Finder::class), service('sylius.context.channel'), service('sylius.context.locale')])
        ->call('setContainer', [service('service_container')])
        ->tag('controller.service_arguments');

    $services->set(ChannelTypeExtension::class)
        ->tag('form.type_extension');

    $services->set('gally.listener.admin.menu_builder', AdminMenuListener::class)
        ->tag('kernel.event_listener', ['event' => 'sylius.menu.admin.main', 'method' => 'addAdminMenuItems']);

    $services->set(ConfigManager::class)
        ->args([service(GallyConfigurationRepository::class), service('sylius.context.channel')]);
};
