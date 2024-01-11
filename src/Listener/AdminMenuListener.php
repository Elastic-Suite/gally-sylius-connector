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

namespace Gally\SyliusPlugin\Listener;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $configurationMenu = $menu
            ->getChild('configuration');

        if (null === $configurationMenu) {
            return;
        }

        $configurationMenu
            ->addChild('new-subitem', ['route' => 'gally_sylius_config'])
            ->setLabel('Gally')
            ->setLabelAttribute('icon', 'search');
    }
}
