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

namespace Gally\SyliusPlugin\Synchronizer;

use Gally\Rest\Model\Catalog;
use Gally\Rest\Model\CatalogCatalogRead;
use Gally\Rest\Model\LocalizedCatalogCatalogRead;
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
    private array $catalogCodes = [];
    private array $localizedCatalogCodes = [];

    public function __construct(
        GallyConfigurationRepository $configurationRepository,
        RestClient $client,
        string $entityClass,
        string $getCollectionMethod,
        string $createEntityMethod,
        string $putEntityMethod,
        string $deleteEntityMethod,
        private RepositoryInterface $channelRepository,
        private LocalizedCatalogSynchronizer $localizedCatalogSynchronizer
    ) {
        parent::__construct(
            $configurationRepository,
            $client,
            $entityClass,
            $getCollectionMethod,
            $createEntityMethod,
            $putEntityMethod,
            $deleteEntityMethod
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
            $localizedCatalog = $this->localizedCatalogSynchronizer->synchronizeItem([
                'channel' => $channel,
                'locale' => $locale,
                'catalog' => $catalog,
            ]);
        }

        return $catalog;
    }

    public function cleanAll(bool $dryRun = true, bool $quiet = false): void
    {
        $this->fetchEntities();

        $this->catalogCodes = array_flip($this->getAllEntityCodes());
        $this->localizedCatalogSynchronizer->fetchEntities();
        $this->localizedCatalogCodes = array_flip($this->localizedCatalogSynchronizer->getAllEntityCodes());

        // Synchronize all channels where the Gally integration is active
        $channels = $this->channelRepository->findBy(['gallyActive' => 1]);

        /** @var Channel[] $channels */
        foreach ($channels as $channel) {
            /** @var LocaleInterface $locale */
            foreach ($channel->getLocales() as $locale) {
                unset($this->localizedCatalogCodes[$channel->getCode() . '_' . $locale->getCode()]);
            }
            unset($this->catalogCodes[$channel->getCode()]);
        }

        foreach (array_flip($this->localizedCatalogCodes) as $localizedCatalogCode) {
            /** @var LocalizedCatalogCatalogRead $localizedCatalog */
            $localizedCatalog = $this->localizedCatalogSynchronizer->getEntityFromApi($localizedCatalogCode);
            if (!$quiet) {
                echo "  Delete localized catalog {$localizedCatalog->getId()}\n";
            }
            if (!$dryRun) {
                $this->localizedCatalogSynchronizer->deleteEntity($localizedCatalog->getId());
            }
        }

        foreach (array_flip($this->catalogCodes) as $catalogCode) {
            /** @var CatalogCatalogRead $catalog */
            $catalog = $this->getEntityFromApi($catalogCode);
            if (!$quiet) {
                echo "  Delete catalog {$catalog->getId()}\n";
            }
            if (!$dryRun) {
                $this->deleteEntity($catalog->getId());
            }
        }
    }
}
