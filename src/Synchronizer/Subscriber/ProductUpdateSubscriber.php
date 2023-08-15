<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Synchronizer\Subscriber;

use Gally\SyliusPlugin\Synchronizer\MetadataSynchronizer;
use Gally\SyliusPlugin\Synchronizer\SourceFieldSynchronizer;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Sylius\Component\Product\Model\ProductAttribute;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class ProductUpdateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected LoggerInterface $logger,
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
            $metadataName = (new ReflectionClass(ProductAttribute::class))->getShortName();
            $metadata = $this->metadataSynchronizer->synchronizeItem(['entity' => $metadataName]);
            $this->sourceFieldSynchronizer->synchronizeItem([
                'metadata' => $metadata,
                'field' => [
                    'code' => $attribute->getCode(),
                    'type' => SourceFieldSynchronizer::getGallyType($attribute->getType()),
                    'labels' => [$attribute->getNameByLocaleCode('de_DE')] //@ToDo Replace with reasonable data and extend that all locales get synced. Use own SourceFieldLabelSynchronizer like in shopware connectotr
                ]
            ]);
        }
    }
}
