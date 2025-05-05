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

namespace Gally\SyliusPlugin\Indexer\Provider;

use Gally\Sdk\Entity\Catalog;
use Gally\Sdk\Entity\LocalizedCatalog;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * Gally Catalog data provider.
 */
class CatalogProvider implements ProviderInterface
{
    /** @var array<Catalog> */
    private array $catalogCache = [];

    public function __construct(
        private RepositoryInterface $channelRepository,
    ) {
    }

    /**
     * @return iterable<LocalizedCatalog>
     */
    public function provide(): iterable
    {
        // synchronize all channels where the Gally integration is active
        $channels = $this->channelRepository->findBy(['gallyActive' => 1]);

        /** @var ChannelInterface $channel */
        foreach ($channels as $channel) {
            /** @var LocaleInterface $locale */
            foreach ($channel->getLocales() as $locale) {
                yield $this->buildLocalizedCatalog($channel, $locale);
            }
        }
    }

    public function buildLocalizedCatalog(ChannelInterface $channel, LocaleInterface $locale): LocalizedCatalog
    {
        if (!\array_key_exists((string) $channel->getCode(), $this->catalogCache)) {
            $this->catalogCache[$channel->getCode()] = new Catalog(
                (string) $channel->getCode(),
                (string) $channel->getName(),
            );
        }

        return new LocalizedCatalog(
            $this->catalogCache[$channel->getCode()],
            $channel->getCode() . '_' . $locale->getCode(),
            (string) $locale->getName(),
            str_replace('-', '_', (string) $locale->getCode()),
            (string) $channel->getBaseCurrency()?->getCode(),
        );
    }
}
