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

use Doctrine\Common\Collections\Collection;
use Gally\Rest\Model\LocalizedCatalog;
use Gally\Rest\Model\MetadataMetadataRead;
use Gally\Rest\Model\ModelInterface;
use Gally\Rest\Model\SourceFieldOptionSourceFieldOptionRead;
use Gally\Rest\Model\SourceFieldOptionSourceFieldOptionWrite;
use Gally\Rest\Model\SourceFieldSourceFieldRead;
use Gally\SyliusPlugin\Api\RestClient;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;
use ReflectionClass;
use Sylius\Component\Product\Model\Product;
use Sylius\Component\Product\Model\ProductAttribute;
use Sylius\Component\Product\Model\ProductOption;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslation;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * Synchronise Sylius Product Attribute Options to Gally Sourcefield Options.
 */
class SourceFieldOptionSynchronizer extends AbstractSynchronizer
{
    private array $sourceFieldOptionCodes = [];

    public function __construct(
        GallyConfigurationRepository $configurationRepository,
        RestClient $client,
        string $entityClass,
        string $getCollectionMethod,
        string $createEntityMethod,
        string $putEntityMethod,
        string $deleteEntityMethod,
        string $bulkEntityMethod,
        private RepositoryInterface $productAttributeRepository,
        private RepositoryInterface $productOptionRepository,
        private SourceFieldSynchronizer $sourceFieldSynchronizer,
        private MetadataSynchronizer $metadataSynchronizer,
        private LocalizedCatalogSynchronizer $localizedCatalogSynchronizer
    ) {
        parent::__construct(
            $configurationRepository,
            $client,
            $entityClass,
            $getCollectionMethod,
            $createEntityMethod,
            $putEntityMethod,
            $deleteEntityMethod,
            $bulkEntityMethod
        );
    }

    public function getIdentity(ModelInterface $entity): string
    {
        /** @var SourceFieldOptionSourceFieldOptionWrite $entity */
        return $entity->getSourceField() . $entity->getCode();
    }

    public function synchronizeAll(): void
    {
        $this->sourceFieldOptionCodes = array_flip($this->getAllEntityCodes());
        $this->sourceFieldSynchronizer->fetchEntities();

        $metadataName = strtolower((new ReflectionClass(Product::class))->getShortName());
        /** @var MetadataMetadataRead $metadata */
        $metadata = $this->metadataSynchronizer->synchronizeItem(['entity' => $metadataName]);

        /** @var ProductAttribute[] $attributes */
        $attributes = $this->productAttributeRepository->findAll();
        foreach ($attributes as $attribute) {
            $options = [];
            if ('select' === $attribute->getType()) {
                $position = 0;
                $configuration = $attribute->getConfiguration();
                $choices = $configuration['choices'] ?? [];
                foreach ($choices as $code => $choice) {
                    $translations = [];
                    foreach ($choice ?? [] as $locale => $translation) {
                        $translations[] = [
                            'locale' => $locale,
                            'translation' => $translation,
                        ];
                    }
                    $sourceField = $this->sourceFieldSynchronizer->getEntityByCode($metadata, $attribute->getCode());
                    $this->synchronizeItem([
                        'sourceField' => $sourceField,
                        'code' => $code,
                        'translations' => $translations,
                        'position' => $position,
                    ]);
                    ++$position;
                }
            }
        }

        /** @var ProductOption[] $options */
        $options = $this->productOptionRepository->findAll();
        foreach ($options as $option) {
            $position = 0;
            /** @var ProductOptionValueInterface $value */
            foreach ($option->getValues() as $value) {
                $translations = [];
                foreach ($value->getTranslations() as $translation) {
                    /** @var ProductOptionValueTranslation $translation */
                    $translations[] = [
                        'locale' => $translation->getLocale(),
                        'translation' => $translation->getValue(),
                    ];
                }

                $sourceField = $this->sourceFieldSynchronizer->getEntityByCode($metadata, $option->getCode());
                $this->synchronizeItem([
                    'sourceField' => $sourceField,
                    'code' => $value->getCode(),
                    'translations' => $translations,
                    'position' => $position,
                ]);

                ++$position;
            }
        }

        $this->runBulk();

        foreach (array_flip($this->sourceFieldOptionCodes) as $sourceFieldOptionCode) {
            /** @var SourceFieldOptionSourceFieldOptionRead $sourceFieldOption */
            $sourceFieldOption = $this->getEntityFromApi($sourceFieldOptionCode);
            $this->deleteEntity($sourceFieldOption->getId());
        }
    }

    public function synchronizeItem(array $params): ?ModelInterface
    {
        /** @var SourceFieldSourceFieldRead $sourceField */
        $sourceField = $params['sourceField'];

        /** @var Collection $translations */
        $translations = $params['translations'];

        $data = [
            'sourceField' => '/source_fields/' . $sourceField->getId(),
            'code' => $params['code'],
            'defaultLabel' => reset($translations)['translation'],
            'position' => $params['position'],
            'labels' => [],
        ];

        foreach ($translations as $translation) {
            $locale = $translation['locale'];
            /** @var LocalizedCatalog $localizedCatalog */
            foreach ($this->localizedCatalogSynchronizer->getLocalizedCatalogByLocale($locale) as $localizedCatalog) {
                $data['labels'][] = [
                    'localizedCatalog' => '/localized_catalogs/' . $localizedCatalog->getId(),
                    'label' => $translation['translation'],
                ];
            }
        }

        $sourceFieldOption = new SourceFieldOptionSourceFieldOptionWrite($data);
        $this->addEntityToBulk($sourceFieldOption);

        unset($this->sourceFieldOptionCodes[$this->getIdentity($sourceFieldOption)]);

        return $sourceFieldOption;
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
        if (1 !== \count($filteredResults)) {
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
