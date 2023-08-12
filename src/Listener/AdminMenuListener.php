<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Listener;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $configurationMenu = $menu
            ->getChild('configuration')
        ;

        if (null === $configurationMenu) {
            return;
        }

        $configurationMenu
            ->addChild('new-subitem', ['route' => 'gally_sylius_config'])
            ->setLabel('Gally')
            ->setLabelAttribute('icon', 'search')
        ;
    }
}
