<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\DependencyInjection;

use Gally\SyliusPlugin\Entity\GallyConfiguration;
use Gally\SyliusPlugin\Entity\GallyConfigurationInterface;
use Gally\SyliusPlugin\Form\GallyConfigurationType;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @psalm-suppress UnusedVariable
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('gally_sylius');

        return $treeBuilder;
    }
}
