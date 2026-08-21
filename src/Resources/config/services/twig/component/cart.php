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
use Gally\SyliusPlugin\Service\RecommendationFinder;
use Gally\SyliusPlugin\Twig\Component\Cart\RecommendationComponent;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set(RecommendationComponent::class)
        ->args([
            service('sylius.context.cart'),
            service(ConfigManager::class),
            service('sylius.repository.product_association_type'),
            service(RecommendationFinder::class),
        ])
        ->tag('sylius.twig_component', ['key' => 'gally_shop:cart:recommendation']);
};
