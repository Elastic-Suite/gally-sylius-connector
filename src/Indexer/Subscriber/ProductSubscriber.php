<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Indexer\Subscriber;

use Gally\SyliusPlugin\Indexer\ProductIndexer;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class ProductSubscriber implements EventSubscriberInterface
{
    public function __construct(private ProductIndexer $productIndexer)
    {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'sylius.product.post_update' => 'onProductUpdate',
            'sylius.product.post_create' => 'onProductUpdate',
            'sylius.product_variant.post_update' => 'onVariantUpdate',
            'sylius.product_variant.post_create' => 'onVariantUpdate',
        ];
    }

    public function onProductUpdate(GenericEvent $event): void
    {
        $product = $event->getSubject();
        if ($product instanceof ProductInterface) {
            $this->productIndexer->reindex([$product->getId()]);
        }
    }

    public function onVariantUpdate(GenericEvent $event): void
    {
        $variant = $event->getSubject();
        if ($variant instanceof ProductVariantInterface) {
            $this->productIndexer->reindex([$variant->getProduct()->getId()]);
        }
    }
}
