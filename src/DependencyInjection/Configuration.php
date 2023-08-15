<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\DependencyInjection;

use Gally\SyliusPlugin\Form\GallyConfigurationType;
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
