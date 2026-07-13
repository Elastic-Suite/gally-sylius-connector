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

namespace Gally\SyliusPlugin\Indexer\Subscriber;

use Gally\SyliusPlugin\Indexer\ProductIndexer;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class ProductSubscriber implements EventSubscriberInterface
{
    /** @var array<int, int|string> */
    private array $idsPendingDeletion = [];

    public function __construct(private ProductIndexer $productIndexer)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.product.post_update' => 'onProductUpdate',
            'sylius.product.post_create' => 'onProductUpdate',
            'sylius.product.pre_delete' => 'onProductPreDelete',
            'sylius.product.post_delete' => 'onProductDelete',
            'sylius.product_variant.post_update' => 'onVariantUpdate',
            'sylius.product_variant.post_create' => 'onVariantUpdate',
            'sylius.product_variant.post_delete' => 'onVariantUpdate',
        ];
    }

    public function onProductUpdate(GenericEvent $event): void
    {
        $product = $event->getSubject();
        if ($product instanceof ProductInterface) {
            $this->productIndexer->reindex([$product->getId()]);
        }
    }

    /**
     * Doctrine nulls out the identifier once the entity is actually removed, so the id
     * has to be captured before deletion (pre_delete) to still be usable in post_delete.
     */
    public function onProductPreDelete(GenericEvent $event): void
    {
        $product = $event->getSubject();
        if ($product instanceof ProductInterface && null !== $product->getId()) {
            /** @var int|string $id */
            $id = $product->getId();
            $this->idsPendingDeletion[spl_object_id($product)] = $id;
        }
    }

    public function onProductDelete(GenericEvent $event): void
    {
        $product = $event->getSubject();
        if ($product instanceof ProductInterface) {
            $productId = $this->idsPendingDeletion[spl_object_id($product)] ?? null;
            unset($this->idsPendingDeletion[spl_object_id($product)]);
            if (null !== $productId) {
                $this->productIndexer->remove([(string) $productId]);
            }
        }
    }

    public function onVariantUpdate(GenericEvent $event): void
    {
        $variant = $event->getSubject();
        if ($variant instanceof ProductVariantInterface && null !== $variant->getProduct()) {
            $this->productIndexer->reindex([$variant->getProduct()->getId()]);
        }
    }
}
