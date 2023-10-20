<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Synchronizer;

use Gally\Rest\Model\Catalog;
use Gally\Rest\Model\LocalizedCatalog;
use Gally\Rest\Model\ModelInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Locale\Model\Locale;

/**
 * Synchronize Sylius Sale Channels locales with Gally localizedCatalogs.
 */
class LocalizedCatalogSynchronizer extends AbstractSynchronizer
{
    private array $localizedCatalogByLocale = [];

    public function getIdentity(ModelInterface $entity): string
    {
        /** @var LocalizedCatalog $entity */
        return $entity->getCode();
    }

    public function synchronizeAll(): void
    {
        throw new \LogicException('Run catalog synchronizer to sync all localized catalog');
    }

    public function synchronizeItem(array $params): ?ModelInterface
    {
        /** @var Channel $channel */
        $channel = $params['channel'];

        /** @var Locale $locale */
        $locale = $params['locale'];

        /** @var Catalog $catalog */
        $catalog = $params['catalog'];

        return $this->createOrUpdateEntity(
            new LocalizedCatalog([
                "name" => $locale->getName(),
                "code" => $channel->getId() . '_' . $locale->getCode(),
                "locale" => str_replace('-', '_', $locale->getCode()),
                "currency" => $channel->getBaseCurrency()->getCode(),
                "isDefault" => $locale->getId() == $channel->getDefaultLocale()->getId(),
                "catalog" => "/catalogs/" . $catalog->getId(),
            ])
        );
    }

    protected function addEntityByIdentity(ModelInterface $entity)
    {
        /** @var LocalizedCatalog $entity */
        parent::addEntityByIdentity($entity);

        if (!array_key_exists($entity->getLocale(), $this->localizedCatalogByLocale)) {
            $this->localizedCatalogByLocale[$entity->getLocale()] = [];
        }

        $this->localizedCatalogByLocale[$entity->getLocale()][$entity->getId()] = $entity;
    }

    public function getLocalizedCatalogByLocale(string $localeCode): array
    {
        if (empty($this->localizedCatalogByLocale)) {
            // Load all entities to be able to check if the asked entity exists.
            $this->fetchEntities();
        }

        return $this->localizedCatalogByLocale[$localeCode] ?? [];
    }

    public function getByIdentity(string $identifier): ?ModelInterface
    {
        if (!$this->allEntityHasBeenFetch) {
            $this->fetchEntities();
        }

        return $this->entityByCode[$identifier] ?? null;
    }
}
