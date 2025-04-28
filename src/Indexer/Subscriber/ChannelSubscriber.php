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

namespace Gally\SyliusPlugin\Indexer\Subscriber;

use Gally\Sdk\Service\StructureSynchonizer;
use Gally\SyliusPlugin\Indexer\Provider\CatalogProvider;
use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class ChannelSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CatalogProvider $catalogProvider,
        private StructureSynchonizer $synchonizer,
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

            /** @var LocaleInterface $locale */
            foreach ($channel->getLocales() as $locale) {
                $localizedCatalog = $this->catalogProvider->buildLocalizedCatalog($channel, $locale);
                $this->synchonizer->syncLocalizedCatalog($localizedCatalog);
            }
        }
    }
}
