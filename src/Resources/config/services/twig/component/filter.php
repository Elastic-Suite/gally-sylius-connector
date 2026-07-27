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
use Gally\SyliusPlugin\Indexer\Provider\CatalogProvider;
use Gally\SyliusPlugin\Search\FilterConverter;
use Gally\SyliusPlugin\Twig\Component\Filter\FacetOptionsComponent;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    // autoconfigure() so the #[AsLiveComponent] attribute registers the "twig.component"
    // tag itself (with live: true, default_action, ...): Sylius' own "sylius.twig_component"
    // tag (used by the other Twig components in this plugin) strips those live-specific
    // attributes, which would silently turn this into a non-reactive static component.
    $services->set(FacetOptionsComponent::class)
        ->autoconfigure()
        ->args([
            service(CatalogProvider::class),
            service(SearchManager::class),
            service('sylius.context.channel'),
            service('sylius.context.locale'),
            service(FilterConverter::class),
        ]);
};
