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
use Gally\Rest\Model\SourceFieldSourceFieldRead;
use Gally\Rest\Model\SourceFieldSourceFieldWrite;
use Gally\SyliusPlugin\Api\RestClient;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;
use Sylius\Component\Product\Model\Product;
use Sylius\Component\Product\Model\ProductAttribute;
use Sylius\Component\Product\Model\ProductOption;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * Synchronise Sylius Product Attributes to Gally SourceFields.
 */
class SourceFieldSynchronizer extends AbstractSynchronizer
{
    private array $sourceFieldCodes = [];

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
        /** @var SourceFieldSourceFieldRead $entity */
        return $entity->getMetadata() . $entity->getCode();
    }

    public function synchronizeAll(): void
    {
        $metadataName = strtolower((new \ReflectionClass(Product::class))->getShortName());
        $metadata = $this->metadataSynchronizer->synchronizeItem(['entity' => $metadataName]);

        /** @var ProductAttribute[] $attributes */
        $attributes = $this->productAttributeRepository->findAll();
        foreach ($attributes as $attribute) {
            $this->synchronizeItem([
                'metadata' => $metadata,
                'field' => [
                    'code' => $attribute->getCode(),
                    'type' => self::getGallyType($attribute->getType()),
                    'translations' => $attribute->getTranslations(),
                ],
            ]);
        }

        /** @var ProductOption[] $options */
        $options = $this->productOptionRepository->findAll();
        foreach ($options as $option) {
            $this->synchronizeItem([
                'metadata' => $metadata,
                'field' => [
                    'code' => $option->getCode(),
                    'type' => self::getGallyType('select'),
                    'translations' => $option->getTranslations(),
                ],
            ]);
        }

        $this->runBulk();
    }

    public function synchronizeItem(array $params): ?ModelInterface
    {
        /** @var MetadataMetadataRead $metadata */
        $metadata = $params['metadata'];

        /** @var array $field */
        $field = $params['field'];

        /** @var Collection $translations */
        $translations = $field['translations'];

        $data = [
            'metadata' => '/metadata/' . $metadata->getId(),
            'code' => $field['code'],
            'type' => $field['type'],
            'defaultLabel' => $translations->first()->getName(),
            'labels' => [],
        ];

        foreach ($translations as $translation) {
            $locale = $translation->getLocale();

            /** @var LocalizedCatalog $localizedCatalog */
            foreach ($this->localizedCatalogSynchronizer->getLocalizedCatalogByLocale($locale) as $localizedCatalog) {
                $data['labels'][] = [
                    'localizedCatalog' => '/localized_catalogs/' . $localizedCatalog->getId(),
                    'label' => $translation->getName(),
                ];
            }
        }

        $sourceField = new SourceFieldSourceFieldWrite($data);
        $this->addEntityToBulk($sourceField);

        return $sourceField;
    }

    public function cleanAll(bool $dryRun = true, bool $quiet = false): void
    {
        $this->sourceFieldCodes = array_flip($this->getAllEntityCodes());

        $metadataName = strtolower((new \ReflectionClass(Product::class))->getShortName());
        /** @var MetadataMetadataRead $metadata */
        $metadata = $this->metadataSynchronizer->synchronizeItem(['entity' => $metadataName]);

        /** @var ProductAttribute[] $attributes */
        $attributes = $this->productAttributeRepository->findAll();
        foreach ($attributes as $attribute) {
            unset($this->sourceFieldCodes['/metadata/' . $metadata->getId() . $attribute->getCode()]);
        }

        /** @var ProductOption[] $options */
        $options = $this->productOptionRepository->findAll();
        foreach ($options as $option) {
            unset($this->sourceFieldCodes['/metadata/' . $metadata->getId() . $option->getCode()]);
        }

        foreach (array_flip($this->sourceFieldCodes) as $sourceFieldCode) {
            /** @var SourceFieldSourceFieldRead $sourceField */
            $sourceField = $this->getEntityFromApi($sourceFieldCode);
            if (!$sourceField->getIsSystem() && !$quiet) {
                echo "  Delete sourceField {$sourceField->getMetadata()} {$sourceField->getCode()}\n";
            }
            if (!$sourceField->getIsSystem() && !$dryRun) {
                $this->deleteEntity($sourceField->getId());
            }
        }
    }

    public static function getGallyType(string $type): string
    {
        switch ($type) {
            case 'integer':
                return 'int';
            case 'percent':
                return 'float';
            case 'date':
                return 'date';
            case 'datetime':
                return 'datetime';
            case 'checkbox':
                return 'boolean';
            case 'select':
                return 'select';
            case 'text':
            case 'textarea':
            default:
                return 'text';
        }
    }

    public function getEntityByCode(MetadataMetadataRead $metadata, string $code): ?ModelInterface
    {
        $key = '/metadata/' . $metadata->getId() . $code;

        return $this->entityByCode[$key] ?? null;
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
}
