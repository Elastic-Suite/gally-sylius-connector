<?php
declare(strict_types=1);

namespace Gally\SyliusPlugin\Synchronizer;

use Gally\Rest\Model\Metadata;
use Gally\Rest\Model\ModelInterface;

final class MetadataSynchronizer extends AbstractSynchronizer
{
    public function synchronizeAll(): void
    {
    }

    public function synchronizeItem(array $params): ?ModelInterface
    {
        return $this->createOrUpdateEntity(new Metadata(["entity" => $params['entity']]));
    }

    public function getIdentity(ModelInterface $entity): string
    {
        /** @var Metadata $entity */
        return $entity->getEntity();
    }

    protected function getEntityFromApi(ModelInterface $entity): ?ModelInterface
    {
        if (!$this->allEntityHasBeenFetch) {
            $this->fetchEntities();
        }

        return $this->entityByCode[$this->getIdentity($entity)] ?? null;
    }
}
