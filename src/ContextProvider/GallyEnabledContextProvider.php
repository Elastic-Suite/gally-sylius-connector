<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\ContextProvider;

use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Sylius\Bundle\UiBundle\ContextProvider\ContextProviderInterface;
use Sylius\Bundle\UiBundle\Registry\TemplateBlock;
use Sylius\Component\Channel\Context\ChannelContextInterface;

class GallyEnabledContextProvider implements ContextProviderInterface
{
    public function __construct(private ChannelContextInterface $channelContext)
    {
    }
    public function provide(array $templateContext, TemplateBlock $templateBlock): array
    {
        $isActive = false;

        $channel = $this->channelContext->getChannel();
        if (($channel instanceof GallyChannelInterface) && ($channel->getGallyActive())) {
            $isActive = true;
        }

        $templateContext['gally_filter_active'] = $isActive;
        return $templateContext;
    }

    public function supports(TemplateBlock $templateBlock): bool
    {
        return 'sylius.shop.product.index.filters' === $templateBlock->getEventName()
            && 'gally_filters' === $templateBlock->getName();
    }
}
