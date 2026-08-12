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

use Gally\Sdk\Service\StructureSynchonizer;
use Gally\SyliusPlugin\Command\Index;
use Gally\SyliusPlugin\Command\StructureClean;
use Gally\SyliusPlugin\Command\StructureSync;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set(StructureSync::class)
        ->args([service(StructureSynchonizer::class), tagged_iterator('gally.dataprovider', 'entity')])
        ->tag('console.command');

    $services->set(StructureClean::class)
        ->args([service(StructureSynchonizer::class), tagged_iterator('gally.dataprovider', 'entity')])
        ->tag('console.command');

    $services->set(Index::class)
        ->args([tagged_iterator('gally.entity.indexer')])
        ->tag('console.command');
};
