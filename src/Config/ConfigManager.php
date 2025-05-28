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

namespace Gally\SyliusPlugin\Config;

use Gally\Sdk\Client\Client;
use Gally\Sdk\Client\Configuration;
use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

class ConfigManager
{
    public function __construct(
        private GallyConfigurationRepository $gallyConfigurationRepository,
        private ChannelContextInterface $channelContext,
    ) {
    }

    public function isGallyEnabled(?ChannelInterface $channel = null): bool
    {
        $isGallyEnabled = false;
        $channel = $channel ?? $this->channelContext->getChannel();

        if (($channel instanceof GallyChannelInterface) && $channel->getGallyActive()) {
            $isGallyEnabled = true;
        }

        return $isGallyEnabled;
    }

    public function testCredentials(): void
    {
        $gallyConfiguration = $this->gallyConfigurationRepository->getConfiguration();
        $configuration = new Configuration(
            $gallyConfiguration->getBaseUrl(),
            $gallyConfiguration->getCheckSSL(),
            $gallyConfiguration->getUserName(),
            $gallyConfiguration->getPassword()
        );
        $client = new Client($configuration);
        $client->get('indices');
    }
}
