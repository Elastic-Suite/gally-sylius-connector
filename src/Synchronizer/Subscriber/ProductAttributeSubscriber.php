<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Synchronizer\Subscriber;

use Gally\SyliusPlugin\Synchronizer\MetadataSynchronizer;
use Gally\SyliusPlugin\Synchronizer\SourceFieldSynchronizer;
use ReflectionClass;
use Sylius\Component\Product\Model\Product;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class ProductAttributeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MetadataSynchronizer $metadataSynchronizer,
        private SourceFieldSynchronizer $sourceFieldSynchronizer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.product_attribute.post_update' => 'onProductUpdate',
            'sylius.product_attribute.post_create' => 'onProductUpdate',
        ];
    }

    public function onProductUpdate(GenericEvent $event): void
    {
        $attribute = $event->getSubject();
        if ($attribute instanceof ProductAttributeInterface) {
            $metadataName = strtolower((new ReflectionClass(Product::class))->getShortName());
            $metadata = $this->metadataSynchronizer->synchronizeItem(['entity' => $metadataName]);

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

            $this->sourceFieldSynchronizer->synchronizeItem([
                'metadata' => $metadata,
                'field' => [
                    'code' => $attribute->getCode(),
                    'type' => SourceFieldSynchronizer::getGallyType((string)$attribute->getType()),
                    'translations' => $attribute->getTranslations(),
                    'options' => $options,
                ]
            ]);
        }
    }
}
