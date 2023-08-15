<?php
declare(strict_types=1);

namespace Gally\SyliusPlugin\Synchronizer;

use Gally\Rest\Model\Metadata;
use Gally\Rest\Model\ModelInterface;
use Gally\Rest\Model\SourceFieldSourceFieldApi;
use Gally\SyliusPlugin\Api\RestClient;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;
use ReflectionClass;
use Sylius\Component\Product\Model\ProductAttribute;
use Sylius\Component\Resource\Repository\RepositoryInterface;

class SourceFieldSynchronizer extends AbstractSynchronizer
{
    private array $entitiesToSync = ['category', 'product', 'manufacturer'];
    private array $staticFields = [
        'product' => [
            'manufacturer' => [
                'type' => 'select',
                'labelKey' => 'listing.filterManufacturerDisplayName'
            ],
            'free_shipping' => [
                'type' => 'boolean',
                'labelKey' => 'listing.filterFreeShippingDisplayName'
            ],
            'rating_avg' => [
                'type' => 'float',
                'labelKey' => 'listing.filterRatingDisplayName'
            ],
            'category' => [
                'type' => 'category',
                'labelKey' => 'general.categories'
            ],
        ],
        'manufacturer' => [
            'id' => 'text',
            'name' => 'text',
            'description' => 'text',
            'link' => 'text',
            'image' => 'text',
        ],
    ];

    public function __construct(
        GallyConfigurationRepository $configurationRepository,
        RestClient $client,
        string $entityClass,
        string $getCollectionMethod,
        string $createEntityMethod,
        string $patchEntityMethod,
        private RepositoryInterface $productAttributeRepository,
        private MetadataSynchronizer $metadataSynchronizer
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
        $attributes = $this->productAttributeRepository->findAll();
        $metadataName = (new ReflectionClass(ProductAttribute::class))->getShortName();
        $metadata = $this->metadataSynchronizer->synchronizeItem(['entity' => $metadataName]);
        foreach ($attributes as $attribute) {
            $this->synchronizeItem([
                'metadata' => $metadata,
                'field' => [
                    'code' => $attribute->getCode(),
                    'type' => SourceFieldSynchronizer::getGallyType($attribute->getType()),
                ]
            ]);
        }
    }

    public function synchronizeItem(array $params): ?ModelInterface
    {
        /** @var Metadata $metadata */
        $metadata = $params['metadata'];

        /** @var array| $field */
        $field = $params['field'];

        $data = ['metadata' => '/metadata/' . $metadata->getId()];

        $data['code'] = $field['code'];
        $data['type'] = $field['type'];
        $labels = $field['labels'] ?? [];
        // Prevent to update system source field
        if ($field['code'] !== 'category') {
            $data['defaultLabel'] = empty($labels) ? $data['code'] : reset($labels);
        }

        return $this->createOrUpdateEntity(new SourceFieldSourceFieldApi($data));
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
