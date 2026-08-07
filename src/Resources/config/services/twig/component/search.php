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

use Gally\SyliusPlugin\Twig\Component\Search\SearchBarComponent;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set(SearchBarComponent::class)
        ->autoconfigure()
        ->args([service('form.factory'), service('router'), service('request_stack')])
        ->tag('sylius.twig_component', ['key' => 'gally_shop:search:search_bar']);
};
