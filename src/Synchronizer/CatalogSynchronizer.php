<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Synchronizer;

use Gally\Rest\Model\Catalog;
use Gally\Rest\Model\ModelInterface;
use Gally\SyliusPlugin\Api\RestClient;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;

use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;


/**
 * Synchronize Sylius Sales Channels with Gally catalogs and localizedCatalogs.
 */
class CatalogSynchronizer extends AbstractSynchronizer
{
    public function __construct(
        GallyConfigurationRepository $configurationRepository,
        RestClient $client,
        string $entityClass,
        string $getCollectionMethod,
        string $createEntityMethod,
        string $patchEntityMethod,
        private RepositoryInterface $channelRepository,
        private LocalizedCatalogSynchronizer $localizedCatalogSynchronizer
    ) {
        parent::__construct(
            $configurationRepository,
            $client,
            $entityClass,
            $getCollectionMethod,
            $createEntityMethod,
            $patchEntityMethod
        );
    }

    public function getIdentity(ModelInterface $entity): string
    {
        /** @var Catalog $entity */
        return $entity->getCode();
    }

    public function synchronizeAll(): void
    {
        $this->fetchEntities();
        $this->localizedCatalogSynchronizer->fetchEntities();

        // synchronize all channels where the Gally integration is active
        $channels = $this->channelRepository->findBy(['gallyActive' => 1]);

        /** @var Channel[] $channels */
        foreach ($channels as $channel) {
            $this->synchronizeItem(['channel' => $channel]);
        }
    }

    public function synchronizeItem(array $params): ?ModelInterface
    {
        /** @var Channel $channel */
        $channel = $params['channel'];

        $catalog = $this->createOrUpdateEntity(
            new Catalog([
                'code' => $channel->getCode(),
                'name' => $channel->getName(),
            ])
        );

        /** @var LocaleInterface $locale */
        foreach ($channel->getLocales() as $locale) {
            $this->localizedCatalogSynchronizer->synchronizeItem([
                'channel' => $channel,
                'locale' => $locale,
                'catalog' => $catalog,
            ]);
        }

        return $catalog;
    }
}
