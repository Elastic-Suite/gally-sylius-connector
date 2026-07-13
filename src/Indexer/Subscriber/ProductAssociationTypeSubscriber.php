<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2026-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\SyliusPlugin\Indexer\Subscriber;

use Gally\Sdk\Service\StructureSynchonizer;
use Gally\SyliusPlugin\Indexer\Provider\RecommenderTypeProvider;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class ProductAssociationTypeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RecommenderTypeProvider $recommenderTypeProvider,
        private StructureSynchonizer $structureSynchonizer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.product_association_type.post_create' => 'onProductAssociationTypeUpdate',
            'sylius.product_association_type.post_update' => 'onProductAssociationTypeUpdate',
        ];
    }

    public function onProductAssociationTypeUpdate(GenericEvent $event): void
    {
        $productAssociationType = $event->getSubject();
        if (!$productAssociationType instanceof ProductAssociationTypeInterface) {
            return;
        }

        $recommenderType = $this->recommenderTypeProvider->buildRecommenderType($productAssociationType);
        $this->structureSynchonizer->syncRecommenderType($recommenderType);
    }
}
