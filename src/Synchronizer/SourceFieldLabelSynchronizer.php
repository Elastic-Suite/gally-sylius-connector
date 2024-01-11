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

use Gally\Rest\Model\ModelInterface;
use Gally\Rest\Model\SourceFieldLabel;
use Gally\SyliusPlugin\Api\RestClient;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;

/**
 * Synchronize Sylius Product Attribute Translations to Gally SourceField Labels.
 */
class SourceFieldLabelSynchronizer extends AbstractSynchronizer
{
    public function __construct(
        GallyConfigurationRepository $configurationRepository,
        RestClient $client,
        string $entityClass,
        string $getCollectionMethod,
        string $createEntityMethod,
        string $putEntityMethod,
        protected LocalizedCatalogSynchronizer $localizedCatalogSynchronizer
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
        /** @var SourceFieldLabel $entity */
        return $entity->getSourceField() . $entity->getLocalizedCatalog();
    }

    public function synchronizeAll(): void
    {
        throw new \LogicException('Run source field synchronizer to sync all localized labels');
    }

    public function synchronizeItem(array $params): ?ModelInterface
    {
        throw new \LogicException('Run source field synchronizer to sync localized label');
    }

    protected function buildFetchAllParams(int $page): array
    {
        return [
            $this->entityClass,
            $this->getCollectionMethod,
            null,
            null,
            null,
            null,
            $page,
            self::FETCH_PAGE_SIZE,
        ];
    }

    protected function buildFetchOneParams(ModelInterface $entity): array
    {
        /** @var SourceFieldLabel $entity */
        return [
            $this->entityClass,
            $this->getCollectionMethod,
            $entity->getLocalizedCatalog(),
            null,
            $entity->getSourceField(),
        ];
    }
}
