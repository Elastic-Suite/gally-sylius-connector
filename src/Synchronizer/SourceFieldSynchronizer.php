<?php
declare(strict_types=1);

namespace Gally\SyliusPlugin\Synchronizer;


use Doctrine\Common\Collections\Collection;
use Gally\Rest\Model\Metadata;
use Gally\Rest\Model\ModelInterface;
use Gally\Rest\Model\SourceFieldSourceFieldApi;
use Gally\SyliusPlugin\Api\RestClient;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;
use ReflectionClass;
use Sylius\Component\Product\Model\Product;
use Sylius\Component\Product\Model\ProductAttribute;
use Sylius\Component\Product\Model\ProductAttributeTranslationInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * Synchronise Sylius Product Attributes to Gally Sourcefields
 */
class SourceFieldSynchronizer extends AbstractSynchronizer
{
    public function __construct(
        GallyConfigurationRepository $configurationRepository,
        RestClient $client,
        string $entityClass,
        string $getCollectionMethod,
        string $createEntityMethod,
        string $patchEntityMethod,
        private RepositoryInterface $productAttributeRepository,
        private MetadataSynchronizer $metadataSynchronizer,
        private SourceFieldLabelSynchronizer $sourceFieldLabelSynchronizer,
        private SourceFieldOptionSynchronizer $sourceFieldOptionSynchronizer
    ) {
        parent::__construct(
            $configurationRepository,
            $client,
            $entityClass,
            $getCollectionMethod,
            $createEntityMethod,
            $patchEntityMethod
        );
    }

    public function getIdentity(ModelInterface $entity): string
    {
        /** @var SourceFieldSourceFieldApi $entity */
        return $entity->getCode();
    }

    public function synchronizeAll(): void
    {
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
        ];

        $sourceField = $this->createOrUpdateEntity(new SourceFieldSourceFieldApi($data));

        foreach ($translations as $translation) {
            /** @var ProductAttributeTranslationInterface $translation */
            $this->sourceFieldLabelSynchronizer->synchronizeItem([
                'field' => $sourceField,
                'locale' => $translation->getLocale(),
                'translation' => $translation->getName(),
            ]);
        }

        foreach ($options as $position => $option) {
            $this->sourceFieldOptionSynchronizer->synchronizeItem([
                'field' => $sourceField,
                'option' => $option,
                'position' => $position,
            ]);
        }

        return $sourceField;
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
}
