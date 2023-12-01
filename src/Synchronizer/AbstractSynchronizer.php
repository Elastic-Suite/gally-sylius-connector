<?php
declare(strict_types=1);

namespace Gally\SyliusPlugin\Synchronizer;

use Gally\SyliusPlugin\Api\RestClient;
use Gally\SyliusPlugin\Entity\GallyConfiguration;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;
use Gally\Rest\Model\ModelInterface;

/**
 * Synchronize shopware store structure with gally.
 */
abstract class AbstractSynchronizer
{
    protected const FETCH_PAGE_SIZE = 50;

    protected array $entityByCode = [];
    protected bool $allEntityHasBeenFetch = false;

    protected GallyConfiguration $configuration;

    public function __construct(
        protected GallyConfigurationRepository $configurationRepository,
        protected RestClient $client,
        protected string $entityClass,
        protected string $getCollectionMethod,
        protected string $createEntityMethod,
        protected string $putEntityMethod
    ) {
        $this->configuration = $this->configurationRepository->getConfiguration();
    }

    abstract public function synchronizeAll(): void;

    abstract public function synchronizeItem(array $params): ?ModelInterface;

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function fetchEntities(): void
    {
        $currentPage = 1;
        do {
            $entities = $this->client->query(...$this->buildFetchAllParams($currentPage));

            foreach ($entities as $entity) {
                $this->addEntityByIdentity($entity);
            }
            $currentPage++;
        } while (count($entities) >= self::FETCH_PAGE_SIZE);
        $this->allEntityHasBeenFetch = true;
    }

    public function fetchEntity(ModelInterface $entity): ?ModelInterface
    {
        $entities = $this->client->query(...$this->buildFetchOneParams($entity));
        if (count($entities) !== 1) {
            return null;
        }
        return reset($entities);
    }

    abstract protected function getIdentity(ModelInterface $entity): string;

    protected function buildFetchAllParams(int $page): array
    {
        return [
            $this->entityClass,
            $this->getCollectionMethod,
            null,
            null,
            $page,
            self::FETCH_PAGE_SIZE
        ];
    }

    protected function buildFetchOneParams(ModelInterface $entity): array
    {
        return [
            $this->entityClass,
            $this->getCollectionMethod,
            $this->getIdentity($entity),
        ];
    }

    protected function createOrUpdateEntity(ModelInterface $entity): ModelInterface
    {
        $this->validateEntity($entity);

        if ($this->getIdentity($entity)) {
            // Check if entity already exists.
            $existingEntity = $this->getEntityFromApi($entity);
            if (!$existingEntity) {
                // Create it if needed. Also save it locally for later use.
                $entity = $this->client->query($this->entityClass, $this->createEntityMethod, $entity);
            } else {
                $entity = $this->client->query(
                    $this->entityClass,
                    $this->putEntityMethod,
                    $existingEntity->getId(), // @phpstan-ignore-line
                    $entity
                );
            }
            $this->addEntityByIdentity($entity);
        }

        return $this->entityByCode[$this->getIdentity($entity)];
    }

    protected function getEntityFromApi(ModelInterface $entity): ?ModelInterface
    {
        if ($this->allEntityHasBeenFetch) {
            return $this->entityByCode[$this->getIdentity($entity)] ?? null;
        }

        return $this->fetchEntity($entity);
    }

    protected function addEntityByIdentity(ModelInterface $entity): void
    {
        $this->entityByCode[$this->getIdentity($entity)] = $entity;
    }

    protected function validateEntity(ModelInterface $entity): void
    {
        if (!$entity->valid()) {
            throw new \LogicException(
                "Missing properties for "
                . get_class($entity) . " : "
                . implode(",", $entity->listInvalidProperties())
            );
        }
    }
}
