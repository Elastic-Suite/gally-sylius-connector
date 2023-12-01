<?php
declare(strict_types=1);

namespace Gally\SyliusPlugin\Synchronizer;


use Doctrine\Common\Collections\Collection;
use Gally\Rest\Model\LocalizedCatalog;
use Gally\Rest\Model\Metadata;
use Gally\Rest\Model\ModelInterface;
use Gally\Rest\Model\SourceFieldLabel;
use Gally\Rest\Model\SourceFieldOptionLabelSourceFieldOptionLabelWrite;
use Gally\Rest\Model\SourceFieldOptionSourceFieldOptionWrite;
use Gally\Rest\Model\SourceFieldSourceFieldRead;
use Gally\Rest\Model\SourceFieldSourceFieldWrite;
use Gally\SyliusPlugin\Api\RestClient;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;
use ReflectionClass;
use Sylius\Component\Product\Model\Product;
use Sylius\Component\Product\Model\ProductAttribute;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * Synchronise Sylius Product Attributes to Gally SourceFields
 */
class SourceFieldSynchronizer extends AbstractSynchronizer
{
    private int $optionBatchSize = 1000;

    public function __construct(
        GallyConfigurationRepository $configurationRepository,
        RestClient $client,
        string $entityClass,
        string $getCollectionMethod,
        string $createEntityMethod,
        string $putEntityMethod,
        private RepositoryInterface $productAttributeRepository,
        private MetadataSynchronizer $metadataSynchronizer,
        private SourceFieldLabelSynchronizer $sourceFieldLabelSynchronizer,
        private SourceFieldOptionSynchronizer $sourceFieldOptionSynchronizer,
        private SourceFieldOptionLabelSynchronizer $sourceFieldOptionLabelSynchronizer,
        private LocalizedCatalogSynchronizer $localizedCatalogSynchronizer
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
        /** @var SourceFieldSourceFieldRead $entity */
        return $entity->getCode();
    }

    public function synchronizeAll(): void
    {
        $this->fetchEntities();

        $metadataName = strtolower((new ReflectionClass(Product::class))->getShortName());
        $metadata = $this->metadataSynchronizer->synchronizeItem(['entity' => $metadataName]);

        /** @var ProductAttribute[] $attributes */
        $attributes = $this->productAttributeRepository->findAll();
        foreach ($attributes as $attribute) {
            $options = [];
            if ($attribute->getType() === 'select') {
                $position = 0;
                $configuration = $attribute->getConfiguration();
                $choices = $configuration['choices'] ?? [];
                foreach ($choices as $code => $choice) {
                    $translations= [];
                    foreach ($choice ?? [] as $locale => $translation) {
                        $translations[] = [
                            'locale' => $locale,
                            'translation' => $translation,
                        ];
                    }

                    $options[$position] = [
                        'code' => $code,
                        'translations' => $translations,
                        'position' => $position,
                    ];

                    $position++;
                }
            }

            $this->synchronizeItem([
                'metadata' => $metadata,
                'field' => [
                    'code' => $attribute->getCode(),
                    'type' => SourceFieldSynchronizer::getGallyType($attribute->getType()),
                    'translations' => $attribute->getTranslations(),
                    'options' => $options,
                ]
            ]);
        }
    }

    public function synchronizeItem(array $params): ?ModelInterface
    {
        /** @var Metadata $metadata */
        $metadata = $params['metadata'];

        /** @var array $field */
        $field = $params['field'];

        /** @var Collection $translations */
        $translations = $field['translations'];

        /** @var array $options */
        $options = $field['options'];

        $data = [
            'metadata' => '/metadata/' . $metadata->getId(),
            'code' => $field['code'],
            'type' => $field['type'],
            'defaultLabel' => $translations->first()->getName(),
            'labels' => [],
        ];

        foreach ($translations as $translation) {
            $tempSourceField = $this->getEntityFromApi(new SourceFieldSourceFieldWrite($data));
            $locale = $translation->getLocale();

            /** @var LocalizedCatalog $localizedCatalog */
            foreach ($this->localizedCatalogSynchronizer->getLocalizedCatalogByLocale($locale) as $localizedCatalog) {
                /** @var SourceFieldLabel $labelObject */
                $labelObject = $tempSourceField
                    ? $this->sourceFieldLabelSynchronizer->getEntityFromApi(
                        new SourceFieldLabel(
                            [
                                'sourceField'      => '/source_fields/' . $tempSourceField->getId() ,
                                'localizedCatalog' => '/localized_catalogs/' . $localizedCatalog->getId(),
                                'label'            =>  $translation->getName(),
                            ]
                        )
                    )
                    : null;

                $labelData = [
                    'localizedCatalog' => '/localized_catalogs/' . $localizedCatalog->getId(),
                    'label' => $translation->getName(),
                ];
                if ($labelObject && $labelObject->getId()) {
                    $labelData['id'] = '/source_field_labels/' . $labelObject->getId();
                }
                $data['labels'][] = $labelData;
            }
        }

        $sourceField = $this->createOrUpdateEntity(new SourceFieldSourceFieldWrite($data));
        $this->addOptions($sourceField, $options ?? []);

        return $sourceField;
    }

    public function fetchEntities(): void
    {
        parent::fetchEntities();
        $this->sourceFieldLabelSynchronizer->fetchEntities();
        $this->sourceFieldOptionSynchronizer->fetchEntities();
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

    protected function addOptions(ModelInterface $sourceField, iterable $options)
    {
        $currentBulkSize = 0;
        $currentBulk = [];

        foreach ($options as $position => $option) {
            /** @var SourceFieldOptionSourceFieldOptionWrite $optionObject */
            $optionObject = $this->sourceFieldOptionSynchronizer->getEntityFromApi(
                new SourceFieldOptionSourceFieldOptionWrite(
                    [
                        'sourceField' => '/source_fields/' . $sourceField->getId(),
                        'code' => $option['code'],
                    ]
                )
            );

            $optionData = [
                'code' => $option['code'],
                'defaultLabel' => $option['translations'][0]['translation'],
                'position' => $position,
                'labels' => [],
            ];
            if ($optionObject && $optionObject->getId()) {
                $optionData['@id'] = '/source_field_options/' . $optionObject->getId();
            }

            // Add option labels
            $labels = $option['translations'] ?? [];
            foreach ($labels as $label) {
                /** @var LocalizedCatalog $localizedCatalog */
                foreach ($this->localizedCatalogSynchronizer->getLocalizedCatalogByLocale($label['locale']) as $localizedCatalog) {
                    /** @var SourceFieldLabel $labelObject */
                    $labelObject = $optionObject
                        ? $this->sourceFieldOptionLabelSynchronizer->getEntityFromApi(
                            new SourceFieldOptionLabelSourceFieldOptionLabelWrite(
                                [
                                    'sourceFieldOption' => '/source_field_options/' . $optionObject->getId(),
                                    'localizedCatalog' => '/localized_catalogs/' . $localizedCatalog->getId(),
                                ]
                            )
                        )
                        : null;

                    $labelData = [
                        'localizedCatalog' => '/localized_catalogs/' . $localizedCatalog->getId(),
                        'label' => $label['translation'],
                    ];
                    if ($labelObject && $labelObject->getId()) {
                        $labelData['@id'] = '/source_field_option_labels/' . $labelObject->getId();
                    }
                    $optionData['labels'][] = $labelData;
                }
            }

            $currentBulk[] = $optionData;
            $currentBulkSize++;
            if ($currentBulkSize > $this->optionBatchSize) {
                $this->client->query($this->entityClass, 'addOptionsSourceFieldItem', $sourceField->getId(), $currentBulk);
                $currentBulkSize = 0;
                $currentBulk = [];
            }
        }

        if ($currentBulkSize) {
            $this->client->query($this->entityClass, 'addOptionsSourceFieldItem', $sourceField->getId(), $currentBulk);
        }
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
