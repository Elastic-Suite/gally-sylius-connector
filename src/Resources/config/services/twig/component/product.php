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
use Gally\SyliusPlugin\Recommendation\RecommendationFinder;
use Gally\SyliusPlugin\Twig\Component\Product\CategoryTrackingComponent;
use Gally\SyliusPlugin\Twig\Component\Product\RecommendationComponent;
use Gally\SyliusPlugin\Twig\Component\Product\SortOptionComponent;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set(CategoryTrackingComponent::class)
        ->args([service('request_stack'), service('sylius.repository.taxon'), service('sylius.context.locale')])
        ->tag('sylius.twig_component', ['key' => 'gally_shop:product:category_tracking']);

    $services->set(SortOptionComponent::class)
        ->args([service(SearchManager::class), service('request_stack'), service('translator'), service('sylius.context.channel')])
        ->tag('sylius.twig_component', ['key' => 'gally_shop:product:sorting']);

    $services->set(RecommendationComponent::class)
        ->autoconfigure()
        ->args([
            service('sylius.context.channel'),
            service(ConfigManager::class),
            service(RecommendationFinder::class),
            service('sylius.repository.product_association_type'),
            service('sylius.repository.product'),
        ]);
};
