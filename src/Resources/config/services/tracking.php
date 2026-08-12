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

use Gally\SyliusPlugin\Controller\Shop\TrackingController;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;
use Gally\SyliusPlugin\Service\TrackingProxyService;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    // Tracking Proxy Service
    $services->set(TrackingProxyService::class)
        ->args([service(GallyConfigurationRepository::class), service('logger'), service('http_client')]);

    // Tracking Controller
    $services->set(TrackingController::class)
        ->public()
        ->args([service(TrackingProxyService::class)])
        ->call('setContainer', [service('service_container')])
        ->tag('controller.service_arguments');
};
