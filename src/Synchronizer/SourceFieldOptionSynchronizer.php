<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Synchronizer;

use Gally\Rest\Model\ModelInterface;
use Gally\Rest\Model\SourceFieldOptionSourceFieldOptionWrite;
use Gally\Rest\Model\SourceFieldSourceFieldWrite;
use Gally\SyliusPlugin\Api\RestClient;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;

/**
 * Synchronise Sylius Product Attribute Options to Gally Sourcefield Options
 */
class SourceFieldOptionSynchronizer extends AbstractSynchronizer
{
    public function __construct(
        GallyConfigurationRepository $configurationRepository,
        RestClient $client,
        string $entityClass,
        string $getCollectionMethod,
        string $createEntityMethod,
        string $putEntityMethod,
        protected SourceFieldOptionLabelSynchronizer $sourceFieldOptionLabelSynchronizer
    ) {
        parent::__construct(
            $configurationRepository,
            $client,
            $entityClass,
            $getCollectionMethod,
            $createEntityMethod,
            $putEntityMethod
        );
    }

    public function getIdentity(ModelInterface $entity): string
    {
        /** @var SourceFieldOptionSourceFieldOptionWrite $entity */
        return $entity->getSourceField() . $entity->getCode();
    }

    public function synchronizeAll(): void
    {
        throw new \LogicException('Run source field synchronizer to sync all options');
    }

    public function synchronizeItem(array $params): ?ModelInterface
    {
        throw new \LogicException('Run source field synchronizer to sync option');
    }

    public function fetchEntities(): void
    {
        parent::fetchEntities();
        $this->sourceFieldOptionLabelSynchronizer->fetchEntities();
    }

    public function fetchEntity(ModelInterface $entity): ?ModelInterface
    {
        /** @var SourceFieldOptionSourceFieldOptionWrite $entity */
        $results = $this->client->query(...$this->buildFetchOneParams($entity));
        $filteredResults = [];
        /** @var SourceFieldOptionSourceFieldOptionWrite $result */
        foreach ($results as $result) {
            // It is not possible to search by source field option code in api.
            // So we need to get the good option after.
            if ($result->getCode() === $entity->getCode()) {
                $filteredResults[] = $result;
            }
        }
        if (count($filteredResults) !== 1) {
            return null;
        }
        return reset($filteredResults);
    }

    protected function buildFetchAllParams(int $page): array
    {
        return [
            $this->entityClass,
            $this->getCollectionMethod,
            null,
            null,
            null,
            $page,
            self::FETCH_PAGE_SIZE,
        ];
    }

    protected function buildFetchOneParams(ModelInterface $entity): array
    {
        /** @var SourceFieldOptionSourceFieldOptionWrite $entity */
        return [
            $this->entityClass,
            $this->getCollectionMethod,
            $entity->getSourceField(),
        ];
    }
}
