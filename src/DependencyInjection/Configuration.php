<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\DependencyInjection;

use Gally\SyliusPlugin\Form\GallyConfigurationType;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
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
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('driver')->defaultValue(SyliusResourceBundle::DRIVER_DOCTRINE_ORM)->end()
                ->arrayNode('resources')->addDefaultsIfNotSet()->end()
            ->end();

        return $treeBuilder;
    }
}
