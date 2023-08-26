<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Synchronizer;

use Gally\Rest\Model\LocalizedCatalog;
use Gally\Rest\Model\ModelInterface;
use Gally\Rest\Model\SourceFieldOptionLabelSourceFieldOptionLabelRead;
use Gally\Rest\Model\SourceFieldOptionLabelSourceFieldOptionLabelWrite;
use Gally\Rest\Model\SourceFieldOptionSourceFieldOptionLabelRead;
use Gally\Rest\Model\SourceFieldOptionSourceFieldOptionWrite;

/**
 * Synchronise Sylius Product Attribute Option Translations to Gally Sourcefield Option Labels
 */
class SourceFieldOptionLabelSynchronizer extends SourceFieldLabelSynchronizer
{
    public function getIdentity(ModelInterface $entity): string
    {
        /** @var SourceFieldOptionLabelSourceFieldOptionLabelRead|SourceFieldOptionLabelSourceFieldOptionLabelWrite $entity */
        /** @var SourceFieldOptionSourceFieldOptionLabelRead|string $sourceFieldOption */
        $sourceFieldOption = $entity->getSourceFieldOption();
        $sourceFieldOption = is_string($sourceFieldOption)
            ? $sourceFieldOption
            : '/source_field_options/' . $sourceFieldOption->getId();
        return $sourceFieldOption . $entity->getLocalizedCatalog();
    }

    public function synchronizeAll(): void
    {
        throw new \LogicException('Run source field synchronizer to sync all localized option labels');
    }

    public function synchronizeItem(array $params): ?ModelInterface
    {
        /** @var SourceFieldOptionSourceFieldOptionWrite $option */
        $option = $params['fieldOption'];

        /** @var string $localeCode */
        $localeCode = $params['localeCode'];

        /** @var string $label */
        $label = $params['label'];

        /** @var LocalizedCatalog $localizedCatalog */
        foreach ($this->localizedCatalogSynchronizer->getLocalizedCatalogByLocale($localeCode) as $localizedCatalog) {
            $this->createOrUpdateEntity(
                new SourceFieldOptionLabelSourceFieldOptionLabelWrite(
                    [
                        'sourceFieldOption' => '/source_field_options/' . $option->getId(),
                        'localizedCatalog' => '/localized_catalogs/' . $localizedCatalog->getId(),
                        'label' => $label,
                    ]
                )
            );
        }

        return null;
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
            null,
            null,
            null,
            $page,
            self::FETCH_PAGE_SIZE,
        ];
    }

    protected function buildFetchOneParams(ModelInterface $entity): array
    {
        /** @var SourceFieldOptionLabelSourceFieldOptionLabelWrite $entity */
        return [
            $this->entityClass,
            $this->getCollectionMethod,
            $entity->getLocalizedCatalog(),
            null,
            $entity->getSourceFieldOption(),
        ];
    }
}
