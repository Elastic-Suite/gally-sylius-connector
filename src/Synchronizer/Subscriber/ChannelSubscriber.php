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

namespace Gally\SyliusPlugin\Synchronizer\Subscriber;

use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Gally\SyliusPlugin\Synchronizer\CatalogSynchronizer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class ChannelSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CatalogSynchronizer $catalogSynchronizer
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.channel.post_update' => 'onChannelUpdate',
            'sylius.channel.post_create' => 'onChannelUpdate',
        ];
    }

    public function onChannelUpdate(GenericEvent $event): void
    {
        $channel = $event->getSubject();
        if ($channel instanceof GallyChannelInterface) {
            if (!$channel->getGallyActive()) {
                return;
            }

            $this->catalogSynchronizer->synchronizeItem(['channel' => $channel]);
        }
    }
}
